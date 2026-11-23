<?php

declare(strict_types=1);

namespace Kongono\Database\Tests;

use Closure;
use Kongono\Database\BatchLoader;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\ConnectionManager;
use Kongono\Database\DatabaseException;
use Kongono\Database\Dialect;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Migrator;
use Kongono\Database\NonTransactionalMigrationInterface;
use Kongono\Database\PdoConnection;
use Kongono\Database\PretendConnection;
use Kongono\Database\QueryBuilder;
use Kongono\Database\QueryGuard;
use Kongono\Database\ReadWriteConnection;
use Kongono\Database\Repository;
use Kongono\Database\RowLevelSecurity;
use Kongono\Database\Schema\Blueprint;
use Kongono\Database\Schema\ColumnDefinition;
use Kongono\Database\Schema\Schema;
use Kongono\Database\Search\MemorySearchEngine;
use Kongono\Database\Search\PostgresFullTextSearch;
use Kongono\Database\Testing\DatabaseTestHelper;
use Kongono\Database\Testing\Factory;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final readonly class NoteRowDto
{
    public function __construct(
        public int $id,
        public string $title,
        public string $createdAt,
    ) {}
}

final class QueryBuilderTest extends TestCase
{
    public function testCompilesFullSelectWithJoinWhereGroupByHavingOrderAndPagination(): void
    {
        $sql = (new QueryBuilder('posts', Dialect::Postgres))
            ->select('posts.author_id', 'users.name AS author_name', 'COUNT(*) AS total')
            ->leftJoin('users', 'users.id', '=', 'posts.author_id')
            ->where('posts.published', '=', true)
            ->whereIn('posts.category', ['php', 'ai'])
            ->whereNotNull('posts.published_at')
            ->groupBy('posts.author_id', 'users.name')
            ->having('COUNT(*)', '>=', 2)
            ->orderBy('posts.author_id', 'DESC')
            ->limit(10)
            ->offset(20)
            ->toSelectSql();

        self::assertSame(
            'SELECT "posts"."author_id", "users"."name" AS "author_name", COUNT(*) AS "total" '
            . 'FROM "posts" '
            . 'LEFT JOIN "users" ON "users"."id" = "posts"."author_id" '
            . 'WHERE "posts"."published" = ? AND "posts"."category" IN (?, ?) AND "posts"."published_at" IS NOT NULL '
            . 'GROUP BY "posts"."author_id", "users"."name" '
            . 'HAVING COUNT(*) >= ? '
            . 'ORDER BY "posts"."author_id" DESC '
            . 'LIMIT 10 OFFSET 20',
            $sql->query,
        );
        self::assertSame([true, 'php', 'ai', 2], $sql->bindings);
    }

    public function testCompilesInsertReturningAndRefusesUnscopedUpdateDelete(): void
    {
        $insert = (new QueryBuilder('notes', Dialect::Postgres))
            ->toInsertSql(['title' => 'Hello', 'body' => 'World'], ['id', 'title']);

        self::assertSame('INSERT INTO "notes" ("title", "body") VALUES (?, ?) RETURNING "id", "title"', $insert->query);
        self::assertSame(['Hello', 'World'], $insert->bindings);

        $update = (new QueryBuilder('notes', Dialect::Mysql))
            ->where('id', '=', 7)
            ->toUpdateSql(['title' => 'Updated']);
        self::assertSame('UPDATE `notes` SET `title` = ? WHERE `id` = ?', $update->query);
        self::assertSame(['Updated', 7], $update->bindings);

        $this->expectException(DatabaseException::class);
        (new QueryBuilder('notes', Dialect::Postgres))->toDeleteSql();
    }

    public function testRejectsUnsafeSqlIdentifiers(): void
    {
        $this->expectException(DatabaseException::class);
        (new QueryBuilder('users; DROP TABLE users;--', Dialect::Postgres))->toSelectSql();
    }

    public function testSchemaBlueprintPaginationAndDtoHydration(): void
    {
        $ddl = [];
        $fakeDb = new class ($ddl) implements ConnectionInterface {
            /** @param list<string> $ddl */
            public function __construct(public array &$ddl) {}

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                return [
                    ['id' => 1, 'title' => 'First', 'created_at' => '2026-11-20T00:00:00Z'],
                    ['id' => 2, 'title' => 'Second', 'created_at' => '2026-11-20T01:00:00Z'],
                ];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                if (str_contains($sql, 'aggregate_count')) {
                    return ['aggregate_count' => 5];
                }

                return ['id' => 1, 'title' => 'First', 'created_at' => '2026-11-20T00:00:00Z'];
            }

            public function execute(string $sql, array $bindings = []): int
            {
                $this->ddl[] = $sql;

                return 1;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }
        };

        Schema::create($fakeDb, 'articles', static function (Blueprint $table): void {
            $table->id();
            $table->string('title', 120);
            $table->boolean('published')->default(false);
            $table->timestamps();
            $table->index(['published']);
        });

        self::assertCount(2, $ddl);
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS "articles" ("id" BIGSERIAL PRIMARY KEY', $ddl[0]);
        self::assertStringContainsString('CREATE INDEX IF NOT EXISTS "idx_articles_published"', $ddl[1]);

        $page = $fakeDb->table('articles')->paginate(page: 1, perPage: 2);
        self::assertSame(5, $page->total);
        self::assertSame(3, $page->lastPage());
        self::assertTrue($page->hasMore());
        self::assertCount(2, $page->items);

        $dtos = $fakeDb->table('articles')->getAs(NoteRowDto::class);
        self::assertCount(2, $dtos);
        self::assertSame('First', $dtos[0]->title);
        self::assertSame('2026-11-20T00:00:00Z', $dtos[0]->createdAt);

        self::assertTrue($fakeDb->table('articles')->exists());
        self::assertSame(['First', 'Second'], $fakeDb->table('articles')->pluck('title'));

        $distinctSql = (new QueryBuilder('articles', Dialect::Postgres))
            ->distinct()
            ->select('category')
            ->whereLike('title', '%kongono%')
            ->whereBetween('views', 10, 100)
            ->toSelectSql();
        self::assertStringContainsString('SELECT DISTINCT "category" FROM "articles" WHERE "title" ILIKE ? AND "views" BETWEEN ? AND ?', $distinctSql->query);
        self::assertSame(['%kongono%', 10, 100], $distinctSql->bindings);

        $batch = (new QueryBuilder('articles', Dialect::Postgres))->toInsertManySql([
            ['title' => 'A', 'views' => 1],
            ['title' => 'B', 'views' => 2],
        ]);
        self::assertSame('INSERT INTO "articles" ("title", "views") VALUES (?, ?), (?, ?)', $batch->query);
        self::assertSame(['A', 1, 'B', 2], $batch->bindings);

        $repo = new readonly class ($fakeDb) extends Repository {
            public function __construct(ConnectionInterface $db)
            {
                parent::__construct($db, 'articles', NoteRowDto::class);
            }
        };
        $found = $repo->findOrFail(1);
        self::assertInstanceOf(NoteRowDto::class, $found);
        self::assertSame('First', $found->title);
        self::assertSame(5, $repo->count());
    }

    public function testPdoConnectionOnQueryListenerRecordsSqlAndDuration(): void
    {
        $fakeStmt = new class extends PDOStatement {
            public function __construct() {}

            public function execute(?array $params = null): bool
            {
                return true;
            }

            /** @return list<array<string, mixed>> */
            public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
            {
                return [['id' => 1]];
            }

            public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
            {
                return ['id' => 1];
            }

            public function rowCount(): int
            {
                return 2;
            }
        };

        $fakePdo = new class ($fakeStmt) extends PDO {
            public function __construct(private readonly PDOStatement $stmt) {}

            public function setAttribute(int $attribute, mixed $value): bool
            {
                return true;
            }

            /** @param array<int, mixed> $options */
            public function prepare(string $query, array $options = []): PDOStatement|false
            {
                return $this->stmt;
            }
        };

        $recorded = [];
        $conn = (new PdoConnection($fakePdo, Dialect::Postgres))
            ->onQuery(static function (string $sql, float $durationMs) use (&$recorded): void {
                $recorded[] = ['sql' => $sql, 'ms' => $durationMs];
            });

        self::assertSame([['id' => 1]], $conn->query('SELECT 1'));
        self::assertSame(['id' => 1], $conn->first('SELECT 2'));
        self::assertSame(2, $conn->execute('UPDATE notes SET title = ?', ['Hi']));

        self::assertCount(3, $recorded);
        self::assertSame('SELECT 1', $recorded[0]['sql']);
        self::assertSame('SELECT 2', $recorded[1]['sql']);
        self::assertSame('UPDATE notes SET title = ?', $recorded[2]['sql']);
        self::assertGreaterThanOrEqual(0.0, $recorded[0]['ms']);
    }

    public function testNestedWhereGroupsUpsertAndMigratorRollback(): void
    {
        $grouped = (new QueryBuilder('users', Dialect::Postgres))
            ->where('active', '=', true)
            ->whereGroup(static function (QueryBuilder $q): void {
                $q->where('role', '=', 'admin')->orWhere('role', '=', 'editor');
            })
            ->orWhereGroup(static function (QueryBuilder $q): void {
                $q->where('vip', '=', true)->where('score', '>=', 100);
            })
            ->toSelectSql();

        self::assertSame(
            'SELECT * FROM "users" WHERE "active" = ? AND ("role" = ? OR "role" = ?) OR ("vip" = ? AND "score" >= ?)',
            $grouped->query,
        );
        self::assertSame([true, 'admin', 'editor', true, 100], $grouped->bindings);

        $pgUpsert = (new QueryBuilder('settings', Dialect::Postgres))
            ->toUpsertSql(
                [['key' => 'theme', 'value' => 'dark'], ['key' => 'lang', 'value' => 'en']],
                ['key'],
                ['value'],
            );
        self::assertSame(
            'INSERT INTO "settings" ("key", "value") VALUES (?, ?), (?, ?) ON CONFLICT ("key") DO UPDATE SET "value" = EXCLUDED."value"',
            $pgUpsert->query,
        );
        self::assertSame(['theme', 'dark', 'lang', 'en'], $pgUpsert->bindings);

        $pgIgnore = (new QueryBuilder('settings', Dialect::Sqlite))
            ->toUpsertSql([['key' => 'theme', 'value' => 'dark']], ['key']);
        self::assertSame('INSERT INTO "settings" ("key", "value") VALUES (?, ?) ON CONFLICT ("key") DO NOTHING', $pgIgnore->query);

        $mysqlUpsert = (new QueryBuilder('settings', Dialect::Mysql))
            ->toUpsertSql([['key' => 'theme', 'value' => 'dark']], ['key'], ['value']);
        self::assertSame('INSERT INTO `settings` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', $mysqlUpsert->query);

        // Verify Migrator::migrate() and Migrator::rollback()
        $events = [];
        $applied = [];
        $fakeConn = new class ($events, $applied) implements ConnectionInterface {
            /**
             * @param list<string> $events
             * @param list<string> $applied
             */
            public function __construct(public array &$events, public array &$applied) {}

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                return array_map(static fn(string $id): array => ['id' => $id], $this->applied);
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                return null;
            }

            public function execute(string $sql, array $bindings = []): int
            {
                if (str_starts_with($sql, 'INSERT INTO "kongono_migrations"') && is_string($bindings[0] ?? null)) {
                    $this->applied[] = $bindings[0];
                } elseif (str_starts_with($sql, 'DELETE FROM "kongono_migrations"') && is_string($bindings[0] ?? null)) {
                    $this->applied = array_values(array_filter($this->applied, static fn(string $id): bool => $id !== $bindings[0]));
                } else {
                    $this->events[] = $sql;
                }

                return 1;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }
        };

        $m1 = new class implements MigrationInterface {
            public function id(): string
            {
                return '20260101_create_a';
            }

            public function up(ConnectionInterface $db): void
            {
                $db->execute('UP_A');
            }

            public function down(ConnectionInterface $db): void
            {
                $db->execute('DOWN_A');
            }
        };
        $m2 = new class implements MigrationInterface {
            public function id(): string
            {
                return '20260102_create_b';
            }

            public function up(ConnectionInterface $db): void
            {
                $db->execute('UP_B');
            }

            public function down(ConnectionInterface $db): void
            {
                $db->execute('DOWN_B');
            }
        };

        $migrator = new Migrator($fakeConn);
        self::assertSame(['20260101_create_a', '20260102_create_b'], $migrator->migrate([$m1, $m2]));
        self::assertSame([
            ['id' => '20260101_create_a', 'applied' => true],
            ['id' => '20260102_create_b', 'applied' => true],
        ], $migrator->status([$m1, $m2]));
        self::assertSame(['20260102_create_b'], $migrator->rollback([$m1, $m2], steps: 1));
        self::assertSame([
            ['id' => '20260101_create_a', 'applied' => true],
            ['id' => '20260102_create_b', 'applied' => false],
        ], $migrator->status([$m1, $m2]));
        self::assertContains('DOWN_B', $events);
        self::assertSame(['20260101_create_a'], $applied);
    }

    public function testFailClosedTenantContextPreventsCrossTenantQueries(): void
    {
        $ctx = new \Kongono\Database\TenantContext(42);
        $scoped = (new QueryBuilder('invoices', Dialect::Postgres))
            ->forTenant($ctx)
            ->where('status', '=', 'open')
            ->toSelectSql();

        self::assertSame('SELECT * FROM "invoices" WHERE "workspace_id" = ? AND "status" = ?', $scoped->query);
        self::assertSame([42, 'open'], $scoped->bindings);

        // Explicit requireTenant() succeeds when where('workspace_id', '=', ...) is present
        $manual = (new QueryBuilder('invoices', Dialect::Postgres))
            ->requireTenant('workspace_id')
            ->where('workspace_id', '=', 7)
            ->toDeleteSql();
        self::assertSame('DELETE FROM "invoices" WHERE "workspace_id" = ?', $manual->query);

        // Unscoped query on a tenant-required table MUST fail closed
        $this->expectException(\Kongono\Database\DatabaseException::class);
        $this->expectExceptionMessage('Refusing cross-tenant SELECT');
        (new QueryBuilder('invoices', Dialect::Postgres))
            ->requireTenant('workspace_id')
            ->where('status', '=', 'open')
            ->toSelectSql();
    }

    public function testQueryGuardDetectsNPlusOneAndBatchLoaderDeduplicates(): void
    {
        // 1. BatchLoader deduplicates keys and avoids redundant queries
        /** @var BatchLoader<int, string> $loader */
        $loader = new BatchLoader();
        $loader->prime(1, 'Ada');
        $batchCalls = [];

        $resolved = $loader->loadMany([1, 2, 3, 2], static function (array $missing) use (&$batchCalls): array {
            $batchCalls[] = $missing;

            return [2 => 'Grace', 3 => 'Alan'];
        });
        self::assertSame([[2, 3]], $batchCalls);
        self::assertSame([1 => 'Ada', 2 => 'Grace', 3 => 'Alan'], $resolved);

        // Second load for cached keys triggers zero DB calls
        $cached = $loader->loadMany([2, 3], static function (array $missing) use (&$batchCalls): array {
            $batchCalls[] = $missing;

            return [];
        });
        self::assertCount(1, $batchCalls);
        self::assertSame([2 => 'Grace', 3 => 'Alan'], $cached);

        // 2. QueryGuard detects N+1 query patterns and resets cleanly between scopes
        $guard = new QueryGuard(threshold: 3, throwOnViolation: false);
        $guard->record("SELECT * FROM orders WHERE user_id = 1 AND status = 'open'");
        $guard->record("SELECT * FROM orders WHERE user_id = 2 AND status = 'open'");
        self::assertFalse($guard->hasViolations());
        $guard->record("SELECT * FROM orders WHERE user_id = 3 AND status = 'paid'");
        self::assertTrue($guard->hasViolations());
        self::assertSame(3, $guard->violations()[0]['count']);
        self::assertSame('SELECT * FROM orders WHERE user_id = ? AND status = ?', $guard->violations()[0]['pattern']);

        $guard->reset();
        self::assertFalse($guard->hasViolations());

        // Strict mode throws DatabaseException immediately on N+1 threshold
        $strictGuard = new QueryGuard(threshold: 2, throwOnViolation: true);
        $strictGuard->record('SELECT * FROM items WHERE id = 10');
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Potential N+1 query detected');
        $strictGuard->record('SELECT * FROM items WHERE id = 11');
    }

    public function testTenantScopeCannotBeBypassedByTopLevelOrWherePrecedence(): void
    {
        // forTenant() + orWhere() MUST parenthesize user OR predicates across SELECT/UPDATE/DELETE
        $select = (new QueryBuilder('invoices', Dialect::Postgres))
            ->forTenant(42)
            ->where('status', '=', 'open')
            ->orWhere('status', '=', 'overdue')
            ->toSelectSql();
        self::assertSame(
            'SELECT * FROM "invoices" WHERE "workspace_id" = ? AND ("status" = ? OR "status" = ?)',
            $select->query,
        );
        self::assertSame([42, 'open', 'overdue'], $select->bindings);

        $update = (new QueryBuilder('invoices', Dialect::Postgres))
            ->forTenant(42)
            ->where('status', '=', 'open')
            ->orWhere('status', '=', 'overdue')
            ->toUpdateSql(['status' => 'archived']);
        self::assertSame(
            'UPDATE "invoices" SET "status" = ? WHERE "workspace_id" = ? AND ("status" = ? OR "status" = ?)',
            $update->query,
        );
        self::assertSame(['archived', 42, 'open', 'overdue'], $update->bindings);

        // requireTenant() + ungrouped top-level orWhere() MUST fail closed
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('top-level OR clause can bypass requireTenant');
        (new QueryBuilder('invoices', Dialect::Postgres))
            ->requireTenant('workspace_id')
            ->where('workspace_id', '=', 42)
            ->orWhere('status', '=', 'overdue')
            ->toSelectSql();
    }

    public function testPdoConnectionRejectsDsnParameterInjection(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Unsafe DSN host');
        PdoConnection::pgsql('127.0.0.1;sslmode=disable', 5432, 'app', 'user', 'pass');
    }

    public function testRound3DatabaseTenantWriteGuardsWhereGroupAndDdlHardening(): void
    {
        // 1. whereGroup() rejects SQLi in $boolean and normalizes lowercase 'or' to prevent tenant bypass
        try {
            (new QueryBuilder('invoices', Dialect::Postgres))
                ->whereGroup(static fn(QueryBuilder $q): QueryBuilder => $q->where('id', '=', 1), 'AND 1=1 OR');
            self::fail('Expected invalid whereGroup boolean to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Unsupported WHERE boolean', $e->getMessage());
        }

        // Lowercase 'or' whereGroup on requireTenant() MUST fail closed
        try {
            (new QueryBuilder('invoices', Dialect::Postgres))
                ->requireTenant('workspace_id')
                ->where('workspace_id', '=', 7)
                ->whereGroup(static fn(QueryBuilder $q): QueryBuilder => $q->where('id', '=', 99), 'or')
                ->toSelectSql();
            self::fail('Expected lowercase or whereGroup to be blocked on requireTenant.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('top-level OR clause', $e->getMessage());
        }

        // 2. matchesTenantColumn() rejects joined-table tenant column satisfying requireTenant()
        try {
            (new QueryBuilder('invoices', Dialect::Postgres))
                ->requireTenant('workspace_id')
                ->leftJoin('other', 'other.id', '=', 'invoices.id')
                ->where('other.workspace_id', '=', 7)
                ->toSelectSql();
            self::fail('Expected joined-table column not to satisfy requireTenant().');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Refusing cross-tenant SELECT', $e->getMessage());
        }

        // 3. forTenant() & requireTenant() enforced on INSERT, INSERT MANY, and UPSERT
        $ins = (new QueryBuilder('invoices', Dialect::Postgres))
            ->forTenant(42)
            ->toInsertSql(['title' => 'Q4']);
        self::assertSame('INSERT INTO "invoices" ("title", "workspace_id") VALUES (?, ?)', $ins->query);
        self::assertSame(['Q4', 42], $ins->bindings);

        // Overriding tenant column with mismatched tenant fails closed
        try {
            (new QueryBuilder('invoices', Dialect::Postgres))
                ->forTenant(42)
                ->toInsertSql(['workspace_id' => 999, 'title' => 'Hijack']);
            self::fail('Expected mismatched tenant on insert to throw.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('conflicts with active forTenant() scope', $e->getMessage());
        }

        // Postgres UPSERT appends tenant WHERE guard on DO UPDATE SET so attacker cannot overwrite another tenant's row
        $upsert = (new QueryBuilder('invoices', Dialect::Postgres))
            ->forTenant(42)
            ->toUpsertSql([['id' => 10, 'title' => 'Safe']], ['id'], ['title']);
        self::assertStringContainsString('ON CONFLICT ("id") DO UPDATE SET "title" = EXCLUDED."title" WHERE "invoices"."workspace_id" = ?', $upsert->query);
        self::assertSame([10, 'Safe', 42, 42], $upsert->bindings);

        // Unscoped INSERT on requireTenant() fails closed
        try {
            (new QueryBuilder('invoices', Dialect::Postgres))
                ->requireTenant('workspace_id')
                ->toInsertSql(['title' => 'NoTenant']);
            self::fail('Expected unscoped insert with requireTenant to throw.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Refusing cross-tenant INSERT', $e->getMessage());
        }

        // 4. PostgreSQL libpq space/quote DSN injection rejected
        try {
            PdoConnection::pgsql('127.0.0.1 sslmode=disable', 5432, 'app', 'user', 'pass');
            self::fail('Expected space-delimited libpq DSN injection to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Unsafe DSN host', $e->getMessage());
        }

        // 5. ColumnDefinition rejects unsafe SQL types and escapes backslashes for MySQL defaults
        $bp = new Blueprint('notes', Dialect::Mysql);
        $bp->string('note')->default("abc\\'; DROP TABLE users;--");
        $mysqlSql = $bp->toSqlStatements();
        self::assertStringContainsString("DEFAULT 'abc\\\\''; DROP TABLE users;--'", $mysqlSql[0]);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Unsafe SQL column type');
        (new ColumnDefinition('evil', 'INT; DROP TABLE users'))->toSql(Dialect::Postgres);
    }

    public function testRound4TenantUpdateUpsertBypassPdoTlsColumnAllowlistQueryGuardAndDialectWildcard(): void
    {
        // 1. toUpdateSql() blocks overwriting the tenant column (case-insensitively)
        foreach (['workspace_id', 'WORKSPACE_ID'] as $col) {
            try {
                (new QueryBuilder('invoices', Dialect::Postgres))
                    ->forTenant(42)
                    ->where('id', '=', 10)
                    ->toUpdateSql([$col => 999, 'status' => 'paid']);
                self::fail('Expected tenant column overwrite in UPDATE to fail for ' . $col);
            } catch (DatabaseException $e) {
                self::assertStringContainsString('cannot modify tenant column', strtolower($e->getMessage()));
            }
        }

        // 2. toUpsertSql() enforces requireTenant() (both missing row tenant AND missing forTenant() scope), blocks case-insensitive tenant update, and emits MySQL conditional IF() guard
        try {
            (new QueryBuilder('invoices', Dialect::Postgres))
                ->requireTenant('workspace_id')
                ->toUpsertSql([['id' => 1, 'title' => 'A']], ['id'], ['title']);
            self::fail('Expected unscoped requireTenant() UPSERT without tenant column to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Refusing cross-tenant INSERT', $e->getMessage());
        }

        try {
            (new QueryBuilder('invoices', Dialect::Postgres))
                ->requireTenant('workspace_id')
                ->toUpsertSql([['id' => 1, 'workspace_id' => 42, 'title' => 'A']], ['id'], ['title']);
            self::fail('Expected requireTenant() UPSERT without forTenant() conflict guard to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Refusing UPSERT with DO UPDATE', $e->getMessage());
        }

        try {
            (new QueryBuilder('invoices', Dialect::Postgres))
                ->forTenant(42)
                ->toUpsertSql([['id' => 1, 'title' => 'A']], ['id'], ['WORKSPACE_ID']);
            self::fail('Expected case-insensitive tenant column update in UPSERT to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('cannot overwrite tenant column', strtolower($e->getMessage()));
        }

        // MySQL UPSERT refuses missing tenant column in conflictColumns AND wraps updates in conditional IF() guard
        try {
            (new QueryBuilder('invoices', Dialect::Mysql))
                ->forTenant(42)
                ->toUpsertSql([['id' => 1, 'title' => 'Unsafe']], ['id'], ['title']);
            self::fail('Expected MySQL UPSERT without tenant in conflictColumns to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Refusing MySQL UPSERT', $e->getMessage());
        }

        $mysqlUpsert = (new QueryBuilder('invoices', Dialect::Mysql))
            ->forTenant(42)
            ->toUpsertSql([['id' => 1, 'title' => 'Safe']], ['id', 'workspace_id'], ['title']);
        self::assertStringContainsString('IF(`workspace_id` = ?, VALUES(`title`), `title`)', $mysqlUpsert->query);
        self::assertSame([1, 'Safe', 42, 42], $mysqlUpsert->bindings);

        // Qualified tenant column normalized cleanly on insert conflict check
        try {
            (new QueryBuilder('invoices', Dialect::Postgres))
                ->forTenant(42, 'invoices.workspace_id')
                ->toInsertSql(['workspace_id' => 999, 'title' => 'Hijack']);
            self::fail('Expected qualified tenant column conflict check to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('conflicts with active forTenant() scope', $e->getMessage());
        }

        // Empty tenant ID rejected
        try {
            (new QueryBuilder('invoices', Dialect::Postgres))->forTenant('');
            self::fail('Expected empty tenant ID to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('cannot be empty', $e->getMessage());
        }

        // Changing required tenant column via requireTenant() after forTenant() resets scoped state
        try {
            (new QueryBuilder('invoices', Dialect::Postgres))
                ->forTenant(42, 'workspace_id')
                ->requireTenant('org_id')
                ->toSelectSql();
            self::fail('Expected requireTenant() with a new column to require scoping on the new column.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Refusing cross-tenant SELECT', $e->getMessage());
        }

        // 3. PdoConnection::fromUrl() rejects unsafe sslmode and NUL bytes in SQLite URL
        try {
            PdoConnection::fromUrl('postgres://user:pass@127.0.0.1:5432/app?sslmode=disable;host=evil');
            self::fail('Expected invalid sslmode to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('sslmode', $e->getMessage());
        }
        try {
            PdoConnection::fromUrl("sqlite:///tmp/app\0.db");
            self::fail('Expected SQLite NUL byte URL to fail.');
        } catch (DatabaseException) {
            self::addToAssertionCount(1);
        }
        try {
            PdoConnection::sqlite("/tmp/app\0.db");
            self::fail('Expected SQLite NUL byte path to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('NUL', $e->getMessage());
        }

        // 4. ColumnDefinition enforces strict SQL type allowlist and rejects NUL bytes
        foreach (["VARCHAR(255)\0", 'TEXT DEFAULT CURRENT_USER REFERENCES users'] as $badType) {
            try {
                (new ColumnDefinition('bio', $badType))->toSql(Dialect::Postgres);
                self::fail('Expected unsafe ColumnDefinition type to fail: ' . $badType);
            } catch (DatabaseException $e) {
                self::assertStringContainsString('Unsafe SQL column type', $e->getMessage());
            }
        }

        // 5. QueryGuard::normalizeSql() handles large 10KB string literals without PCRE stack overflow
        $bigSql = "SELECT * FROM notes WHERE body = '" . str_repeat('a', 10_000) . "' AND id = 42";
        $normalized = QueryGuard::normalizeSql($bigSql);
        self::assertSame('SELECT * FROM notes WHERE body = ? AND id = ?', $normalized);

        // 6. Dialect::quoteIdentifier() allows table.* but rejects non-terminal *.foo
        self::assertSame('"posts".*', Dialect::Postgres->quoteIdentifier('posts.*'));
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Unsafe SQL identifier');
        Dialect::Postgres->quoteIdentifier('*.foo');
    }

    public function testPostgreSqlRowLevelSecurityDdlBlueprintAndTransactionLocalScoping(): void
    {
        // 1. Blueprint::rowLevelSecurity() emits fail-closed PostgreSQL FORCE RLS DDL
        $bp = new Blueprint('invoices', Dialect::Postgres);
        $bp->id();
        $bp->bigInteger('workspace_id');
        $bp->string('title');
        $bp->rowLevelSecurity('workspace_id');
        $stmts = $bp->toSqlStatements();

        self::assertContains('ALTER TABLE "invoices" ENABLE ROW LEVEL SECURITY', $stmts);
        self::assertContains('ALTER TABLE "invoices" FORCE ROW LEVEL SECURITY', $stmts);
        self::assertContains(
            'CREATE POLICY "kongono_rls_invoices_workspace_id" ON "invoices" '
            . 'USING ("workspace_id"::text = NULLIF(current_setting(\'app.current_tenant\', true), \'\')) '
            . 'WITH CHECK ("workspace_id"::text = NULLIF(current_setting(\'app.current_tenant\', true), \'\'))',
            $stmts,
        );

        // Disable SQL cleanly rolls back policy
        $down = RowLevelSecurity::disableSql('invoices');
        self::assertSame([
            'DROP POLICY IF EXISTS "kongono_rls_invoices_workspace_id" ON "invoices"',
            'ALTER TABLE "invoices" NO FORCE ROW LEVEL SECURITY',
            'ALTER TABLE "invoices" DISABLE ROW LEVEL SECURITY',
        ], $down);

        // 2. RowLevelSecurity::withTenant() invokes transaction-local set_config(setting, tenantId, true)
        $calls = [];
        $fakePg = new class ($calls) implements ConnectionInterface {
            /** @param list<array{sql: string, bindings: list<mixed>}> $calls */
            public function __construct(public array &$calls) {}

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                $this->calls[] = ['sql' => $sql, 'bindings' => $bindings];

                return [];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                $this->calls[] = ['sql' => $sql, 'bindings' => $bindings];

                return ['set_config' => '42'];
            }

            public function execute(string $sql, array $bindings = []): int
            {
                $this->calls[] = ['sql' => $sql, 'bindings' => $bindings];

                return 1;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                $this->calls[] = ['sql' => 'BEGIN', 'bindings' => []];
                $res = $callback($this);
                $this->calls[] = ['sql' => 'COMMIT', 'bindings' => []];

                return $res;
            }
        };

        $out = RowLevelSecurity::withTenant($fakePg, new \Kongono\Database\TenantContext(42), static function (ConnectionInterface $tx): string {
            $tx->query('SELECT * FROM "invoices"');

            return 'ok';
        });
        self::assertSame('ok', $out);
        self::assertSame('BEGIN', $calls[0]['sql']);
        self::assertSame('SELECT set_config(?, ?, true)', $calls[1]['sql']);
        self::assertSame(['app.current_tenant', '42'], $calls[1]['bindings']);
        self::assertSame('COMMIT', $calls[3]['sql']);

        // 3. Rejects SQLi setting names, empty tenant IDs, and non-PostgreSQL connections
        try {
            RowLevelSecurity::enableSql('invoices', settingName: "app.x'; DROP TABLE invoices;--");
            self::fail('Expected unsafe RLS setting name to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Unsafe PostgreSQL RLS setting name', $e->getMessage());
        }

        try {
            RowLevelSecurity::withTenant($fakePg, '', static fn(): string => 'nope');
            self::fail('Expected empty tenant ID to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('non-empty', $e->getMessage());
        }

        $fakeSqlite = new class implements ConnectionInterface {
            public function dialect(): Dialect
            {
                return Dialect::Sqlite;
            }

            public function query(string $sql, array $bindings = []): array
            {
                return [];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                return null;
            }

            public function execute(string $sql, array $bindings = []): int
            {
                return 0;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, Dialect::Sqlite, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }
        };
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('requires PostgreSQL');
        RowLevelSecurity::withTenant($fakeSqlite, 42, static fn(): string => 'nope');
    }

    public function testBlueprintNewColumnHelpersForeignKeysSchemaTableAndRenameAcrossDialects(): void
    {
        // 1. PostgreSQL: UUID, JSONB, NUMERIC(p,s), FOREIGN KEY / foreignId, and custom index name
        $pgBp = new Blueprint('orders', Dialect::Postgres);
        $pgBp->uuid('id')->primary();
        $pgBp->foreignId('user_id', 'users', 'id', onDelete: 'CASCADE', onUpdate: 'RESTRICT');
        $pgBp->decimal('total_amount', 14, 4)->default(0);
        $pgBp->json('metadata')->default('{}');
        $pgBp->index(['user_id'], name: 'idx_orders_custom_user');
        $pgSql = $pgBp->toSqlStatements();

        self::assertCount(2, $pgSql);
        self::assertSame(
            'CREATE TABLE IF NOT EXISTS "orders" ('
            . '"id" UUID PRIMARY KEY NOT NULL, '
            . '"user_id" BIGINT NOT NULL, '
            . '"total_amount" NUMERIC(14,4) NOT NULL DEFAULT 0, '
            . '"metadata" JSONB NOT NULL DEFAULT \'{}\', '
            . 'CONSTRAINT "fk_orders_user_id" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE ON UPDATE RESTRICT'
            . ')',
            $pgSql[0],
        );
        self::assertSame('CREATE INDEX IF NOT EXISTS "idx_orders_custom_user" ON "orders" ("user_id")', $pgSql[1]);

        // 2. MySQL: CHAR(36), JSON, DECIMAL(p,s), backtick identifiers, and CREATE INDEX without IF NOT EXISTS
        $myBp = new Blueprint('orders', Dialect::Mysql);
        $myBp->uuid('id')->primary();
        $myBp->decimal('amount', 12, 2);
        $myBp->json('payload')->nullable();
        $myBp->foreignKey('org_id', 'orgs', 'id', onDelete: 'SET NULL');
        $myBp->index(['org_id'], unique: true);
        $mySql = $myBp->toSqlStatements();

        self::assertStringContainsString('`id` CHAR(36) PRIMARY KEY NOT NULL', $mySql[0]);
        self::assertStringContainsString('`amount` DECIMAL(12,2) NOT NULL', $mySql[0]);
        self::assertStringContainsString('`payload` JSON NULL', $mySql[0]);
        self::assertStringContainsString('CONSTRAINT `fk_orders_org_id` FOREIGN KEY (`org_id`) REFERENCES `orgs` (`id`) ON DELETE SET NULL', $mySql[0]);
        self::assertSame('CREATE UNIQUE INDEX `idx_orders_org_id` ON `orders` (`org_id`)', $mySql[1]);

        // 3. SQLite: TEXT for uuid/json, NUMERIC(p,s) for decimal
        $sqBp = new Blueprint('orders', Dialect::Sqlite);
        $sqBp->uuid('id')->primary();
        $sqBp->json('meta');
        $sqBp->decimal('price');
        $sqSql = $sqBp->toSqlStatements();
        self::assertStringContainsString('"id" TEXT PRIMARY KEY NOT NULL', $sqSql[0]);
        self::assertStringContainsString('"meta" TEXT NOT NULL', $sqSql[0]);
        self::assertStringContainsString('"price" NUMERIC(12,2) NOT NULL', $sqSql[0]);

        // 4. Schema::table() alterations + Schema::rename() across PostgreSQL, MySQL, and SQLite
        $pgConn = new PretendConnection(Dialect::Postgres);
        Schema::table($pgConn, 'orders', static function (Blueprint $table): void {
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->uuid('external_ref')->nullable();
            $table->renameColumn('note', 'memo');
            $table->foreignKey('coupon_id', 'coupons', 'id', onDelete: 'SET NULL');
            $table->index(['external_ref'], unique: true, concurrently: true);
            $table->dropIndex(['legacy_status'], concurrently: true);
            $table->dropForeign('fk_orders_legacy_id');
            $table->dropColumn('legacy_status', 'legacy_flag');
            $table->rowLevelSecurity('workspace_id');
        });
        Schema::rename($pgConn, 'orders', 'customer_orders');

        $alterPg = $pgConn->statements();
        self::assertContains('ALTER TABLE "orders" ADD COLUMN "tax_amount" NUMERIC(12,2) NOT NULL DEFAULT 0', $alterPg);
        self::assertContains('ALTER TABLE "orders" ADD COLUMN "external_ref" UUID NULL', $alterPg);
        self::assertContains('ALTER TABLE "orders" RENAME COLUMN "note" TO "memo"', $alterPg);
        self::assertContains('ALTER TABLE "orders" ADD CONSTRAINT "fk_orders_coupon_id" FOREIGN KEY ("coupon_id") REFERENCES "coupons" ("id") ON DELETE SET NULL', $alterPg);
        self::assertContains('CREATE UNIQUE INDEX CONCURRENTLY IF NOT EXISTS "idx_orders_external_ref" ON "orders" ("external_ref")', $alterPg);
        self::assertContains('DROP INDEX CONCURRENTLY IF EXISTS "idx_orders_legacy_status"', $alterPg);
        self::assertContains('ALTER TABLE "orders" DROP CONSTRAINT IF EXISTS "fk_orders_legacy_id"', $alterPg);
        self::assertContains('ALTER TABLE "orders" DROP COLUMN "legacy_status"', $alterPg);
        self::assertContains('ALTER TABLE "orders" DROP COLUMN "legacy_flag"', $alterPg);
        self::assertContains('ALTER TABLE "orders" FORCE ROW LEVEL SECURITY', $alterPg);
        self::assertContains('ALTER TABLE "orders" RENAME TO "customer_orders"', $alterPg);

        // Verify dropIndex and dropForeign occur BEFORE dropColumn in alter order
        $dropIdxPos = array_search('DROP INDEX CONCURRENTLY IF EXISTS "idx_orders_legacy_status"', $alterPg, true);
        $dropFkPos = array_search('ALTER TABLE "orders" DROP CONSTRAINT IF EXISTS "fk_orders_legacy_id"', $alterPg, true);
        $dropColPos = array_search('ALTER TABLE "orders" DROP COLUMN "legacy_status"', $alterPg, true);
        self::assertIsInt($dropIdxPos);
        self::assertIsInt($dropFkPos);
        self::assertIsInt($dropColPos);
        self::assertLessThan($dropColPos, $dropIdxPos);
        self::assertLessThan($dropColPos, $dropFkPos);

        // MySQL alter & rename syntax
        $myConn = new PretendConnection(Dialect::Mysql);
        Schema::table($myConn, 'orders', static function (Blueprint $table): void {
            $table->dropIndex('idx_orders_status');
            $table->dropForeign('fk_orders_user_id');
            $table->dropColumn('old_col');
        });
        Schema::rename($myConn, 'orders', 'customer_orders');
        self::assertSame([
            'DROP INDEX `idx_orders_status` ON `orders`',
            'ALTER TABLE `orders` DROP FOREIGN KEY `fk_orders_user_id`',
            'ALTER TABLE `orders` DROP COLUMN `old_col`',
            'RENAME TABLE `orders` TO `customer_orders`',
        ], $myConn->statements());

        // 5. Validation & fail-closed guards on Blueprint
        try {
            (new Blueprint('orders', Dialect::Postgres))->decimal('bad', 0, 0);
            self::fail('Expected invalid decimal precision to throw.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('precision', $e->getMessage());
        }

        try {
            (new Blueprint('orders', Dialect::Postgres))->decimal('bad', 5, 6);
            self::fail('Expected decimal scale > precision to throw.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('scale', $e->getMessage());
        }

        try {
            (new Blueprint('orders', Dialect::Postgres))->foreignKey('user_id', 'users', 'id', onDelete: 'CASCADE; DROP TABLE users');
            self::fail('Expected SQLi ON DELETE action to throw.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Unsupported foreign key ON DELETE action', $e->getMessage());
        }

        try {
            $badCreate = new Blueprint('orders', Dialect::Postgres);
            $badCreate->id();
            $badCreate->dropColumn('oops');
            $badCreate->toSqlStatements();
            self::fail('Expected alter-only operation in Schema::create() to throw.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('Schema::table()', $e->getMessage());
        }

        try {
            $sqAlter = new PretendConnection(Dialect::Sqlite);
            Schema::table($sqAlter, 'orders', static function (Blueprint $table): void {
                $table->foreignKey('user_id', 'users');
            });
            self::fail('Expected SQLite ALTER TABLE ADD FOREIGN KEY to fail closed.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('SQLite does not support adding foreign key', $e->getMessage());
        }
    }

    public function testMigratorPretendFreshAndNonTransactionalMigrations(): void
    {
        $executed = [];
        $applied = [];
        $txCount = 0;
        $migrationsTableExists = false;

        $fakePg = new class ($executed, $applied, $txCount, $migrationsTableExists) implements ConnectionInterface {
            /**
             * @param list<string> $executed
             * @param list<string> $applied
             */
            public function __construct(
                public array &$executed,
                public array &$applied,
                public int &$txCount,
                public bool &$migrationsTableExists,
            ) {}

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                if (str_contains($sql, 'FROM pg_tables')) {
                    return [
                        ['name' => 'kongono_migrations'],
                        ['name' => 'orders'],
                    ];
                }
                if (!$this->migrationsTableExists) {
                    throw new DatabaseException('relation "kongono_migrations" does not exist');
                }

                return array_map(static fn(string $id): array => ['id' => $id], $this->applied);
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                return null;
            }

            public function execute(string $sql, array $bindings = []): int
            {
                $this->executed[] = $sql;
                if (str_contains($sql, 'CREATE TABLE IF NOT EXISTS "kongono_migrations"')) {
                    $this->migrationsTableExists = true;
                } elseif (str_starts_with($sql, 'INSERT INTO "kongono_migrations"') && is_string($bindings[0] ?? null)) {
                    $this->applied[] = $bindings[0];
                } elseif (str_starts_with($sql, 'DELETE FROM "kongono_migrations"') && is_string($bindings[0] ?? null)) {
                    $this->applied = array_values(array_filter($this->applied, static fn(string $id): bool => $id !== $bindings[0]));
                } elseif (str_starts_with($sql, 'DROP TABLE IF EXISTS "kongono_migrations"')) {
                    $this->migrationsTableExists = false;
                    $this->applied = [];
                }

                return 1;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                ++$this->txCount;

                return $callback($this);
            }
        };

        $m1 = new class implements MigrationInterface {
            public function id(): string
            {
                return '20261122_01_create_orders';
            }

            public function up(ConnectionInterface $db): void
            {
                Schema::create($db, 'orders', static function (Blueprint $table): void {
                    $table->uuid('id')->primary();
                    $table->decimal('total', 12, 2);
                });
            }

            public function down(ConnectionInterface $db): void
            {
                Schema::dropIfExists($db, 'orders');
            }
        };

        $m2 = new class implements NonTransactionalMigrationInterface {
            public function id(): string
            {
                return '20261122_02_index_orders_concurrently';
            }

            public function up(ConnectionInterface $db): void
            {
                Schema::table($db, 'orders', static function (Blueprint $table): void {
                    $table->index(['total'], concurrently: true);
                });
            }

            public function down(ConnectionInterface $db): void
            {
                Schema::table($db, 'orders', static function (Blueprint $table): void {
                    $table->dropIndex(['total'], concurrently: true);
                });
            }
        };

        $migrator = new Migrator($fakePg);

        // 1. pretend() on fresh DB (no kongono_migrations table yet) MUST NOT execute any write SQL
        $previewAll = $migrator->pretend([$m1, $m2]);
        self::assertSame([], $executed);
        self::assertFalse($migrationsTableExists);
        self::assertSame(['20261122_01_create_orders', '20261122_02_index_orders_concurrently'], array_keys($previewAll));
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS "orders" ("id" UUID PRIMARY KEY NOT NULL, "total" NUMERIC(12,2) NOT NULL)', $previewAll['20261122_01_create_orders'][0]);
        self::assertSame(['CREATE INDEX CONCURRENTLY IF NOT EXISTS "idx_orders_total" ON "orders" ("total")'], $previewAll['20261122_02_index_orders_concurrently']);

        // 2. migrate() runs m1 inside a transaction, and m2 (NonTransactionalMigrationInterface) outside a transaction!
        self::assertSame(['20261122_01_create_orders', '20261122_02_index_orders_concurrently'], $migrator->migrate([$m1, $m2]));
        self::assertSame(1, $txCount);

        // 3. pretend() after all migrations applied returns empty map
        self::assertSame([], $migrator->pretend([$m1, $m2]));

        // 4. rollback() respects NonTransactionalMigrationInterface (txCount remains 1)
        self::assertSame(['20261122_02_index_orders_concurrently'], $migrator->rollback([$m1, $m2], steps: 1));
        self::assertSame(1, $txCount);
        self::assertContains('DROP INDEX CONCURRENTLY IF EXISTS "idx_orders_total"', $executed);

        // 5. pretend() now previews ONLY the pending migration m2
        $previewPending = $migrator->pretend([$m1, $m2]);
        self::assertSame(['20261122_02_index_orders_concurrently'], array_keys($previewPending));

        // 6. fresh() drops all tables via CASCADE and re-runs every migration
        $freshResult = $migrator->fresh([$m1, $m2]);
        self::assertSame(['kongono_migrations', 'orders'], $freshResult['dropped']);
        self::assertSame(['20261122_01_create_orders', '20261122_02_index_orders_concurrently'], $freshResult['applied']);
        self::assertContains('DROP TABLE IF EXISTS "orders" CASCADE', $executed);

        // 7. Verify SQLite and MySQL dropAllTables() toggle FK checks cleanly
        foreach ([Dialect::Sqlite, Dialect::Mysql] as $dialect) {
            $log = [];
            $conn = new class ($dialect, $log) implements ConnectionInterface {
                /** @param list<string> $log */
                public function __construct(private readonly Dialect $dialect, public array &$log) {}

                public function dialect(): Dialect
                {
                    return $this->dialect;
                }

                public function query(string $sql, array $bindings = []): array
                {
                    return [['name' => 'items'], ['name' => 'kongono_migrations']];
                }

                public function first(string $sql, array $bindings = []): ?array
                {
                    return null;
                }

                public function execute(string $sql, array $bindings = []): int
                {
                    $this->log[] = $sql;

                    return 1;
                }

                public function table(string $table, ?string $alias = null): QueryBuilder
                {
                    return new QueryBuilder($table, $this->dialect, $this, $alias);
                }

                public function transaction(Closure $callback): mixed
                {
                    return $callback($this);
                }
            };
            $dropped = (new Migrator($conn))->dropAllTables();
            self::assertSame(['items', 'kongono_migrations'], $dropped);
            if (Dialect::Sqlite === $dialect) {
                self::assertSame([
                    'PRAGMA foreign_keys = OFF',
                    'DROP TABLE IF EXISTS "items"',
                    'DROP TABLE IF EXISTS "kongono_migrations"',
                    'PRAGMA foreign_keys = ON',
                ], $log);
            } else {
                self::assertSame([
                    'SET FOREIGN_KEY_CHECKS = 0',
                    'DROP TABLE IF EXISTS `items`',
                    'DROP TABLE IF EXISTS `kongono_migrations`',
                    'SET FOREIGN_KEY_CHECKS = 1',
                ], $log);
            }
        }
    }

    public function testDatabaseTestHelperRollbackIsolationNestedSavepointsAndAssertions(): void
    {
        // 1. Verify PdoConnection nested transaction SAVEPOINT / RELEASE / ROLLBACK SQL deterministically (driver-independent)
        $pdoEvents = [];
        $fakePdo = new class ($pdoEvents) extends PDO {
            private bool $active = false;

            /** @param list<string> $events */
            public function __construct(public array &$events) {}

            public function setAttribute(int $attribute, mixed $value): bool
            {
                return true;
            }

            public function inTransaction(): bool
            {
                return $this->active;
            }

            public function beginTransaction(): bool
            {
                $this->active = true;
                $this->events[] = 'BEGIN';

                return true;
            }

            public function commit(): bool
            {
                $this->active = false;
                $this->events[] = 'COMMIT';

                return true;
            }

            public function rollBack(): bool
            {
                $this->active = false;
                $this->events[] = 'ROLLBACK';

                return true;
            }

            public function exec(string $statement): int|false
            {
                $this->events[] = $statement;

                return 0;
            }
        };

        $pdoConn = new PdoConnection($fakePdo, Dialect::Postgres);
        $helperOnPdo = new DatabaseTestHelper($pdoConn);
        $ret = $helperOnPdo->inRollbackTransaction(static function (ConnectionInterface $tx): string {
            try {
                $tx->transaction(static function (): never {
                    throw new DatabaseException('inner failure');
                });
            } catch (DatabaseException) {
                // inner savepoint rolled back
            }
            $tx->transaction(static fn(): bool => true);

            return 'ok';
        });
        self::assertSame('ok', $ret);
        self::assertSame([
            'BEGIN',
            'SAVEPOINT kongono_sp_2',
            'ROLLBACK TO SAVEPOINT kongono_sp_2',
            'SAVEPOINT kongono_sp_2',
            'RELEASE SAVEPOINT kongono_sp_2',
            'ROLLBACK',
        ], $pdoEvents);

        // 2. Verify DatabaseTestHelper row assertions & rollback state isolation on an in-memory transactional ConnectionInterface
        $memConn = new class implements ConnectionInterface {
            /** @var list<array<string, mixed>> */
            public array $rows = [
                ['email' => 'seed@kongono.dev', 'tier' => 'pro'],
            ];

            /** @var list<string> */
            public array $queries = [];

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                $this->queries[] = $sql;

                return [['aggregate_count' => $this->countRows($sql, $bindings)]];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                $this->queries[] = $sql;

                return ['aggregate_count' => $this->countRows($sql, $bindings)];
            }

            public function execute(string $sql, array $bindings = []): int
            {
                $this->queries[] = $sql;
                if (str_starts_with($sql, 'INSERT INTO "accounts"')) {
                    $this->rows[] = ['email' => $bindings[0] ?? null, 'tier' => $bindings[1] ?? null];

                    return 1;
                }

                return 0;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                $snapshot = $this->rows;
                try {
                    return $callback($this);
                } catch (\Throwable $e) {
                    $this->rows = $snapshot;
                    throw $e;
                }
            }

            /** @param list<mixed> $bindings */
            private function countRows(string $sql, array $bindings): int
            {
                $matched = 0;
                foreach ($this->rows as $row) {
                    $ok = true;
                    $idx = 0;
                    if (str_contains($sql, '"email" = ?')) {
                        $ok = $ok && ($row['email'] === ($bindings[$idx] ?? null));
                        ++$idx;
                    }
                    if (str_contains($sql, '"tier" IS NULL')) {
                        $ok = $ok && (null === $row['tier']);
                    } elseif (str_contains($sql, '"tier" = ?')) {
                        $ok = $ok && ($row['tier'] === ($bindings[$idx] ?? null));
                    }
                    if ($ok) {
                        ++$matched;
                    }
                }

                return $matched;
            }
        };

        $helper = new DatabaseTestHelper($memConn);
        $helper
            ->assertDatabaseCount('accounts', 1)
            ->assertDatabaseHas('accounts', ['email' => 'seed@kongono.dev', 'tier' => 'pro'])
            ->assertDatabaseMissing('accounts', ['email' => 'temp@kongono.dev']);

        $out = $helper->inRollbackTransaction(static function (ConnectionInterface $tx) use ($helper): string {
            $tx->table('accounts')->insert(['email' => 'temp@kongono.dev', 'tier' => null]);
            $helper
                ->assertDatabaseCount('accounts', 2)
                ->assertDatabaseHas('accounts', ['email' => 'temp@kongono.dev', 'tier' => null]);

            return 'done';
        });
        self::assertSame('done', $out);

        // Mutations rolled back cleanly
        $helper
            ->assertDatabaseCount('accounts', 1)
            ->assertDatabaseHas('accounts', ['email' => 'seed@kongono.dev'])
            ->assertDatabaseMissing('accounts', ['email' => 'temp@kongono.dev']);

        // Failed assertions throw DatabaseException with clear diagnostics
        try {
            $helper->assertDatabaseHas('accounts', ['email' => 'nope@kongono.dev']);
            self::fail('Expected assertDatabaseHas to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('contains a row matching', $e->getMessage());
        }

        try {
            $helper->assertDatabaseMissing('accounts', ['email' => 'seed@kongono.dev']);
            self::fail('Expected assertDatabaseMissing to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('is missing rows matching', $e->getMessage());
        }

        try {
            $helper->assertDatabaseCount('accounts', 99);
            self::fail('Expected assertDatabaseCount to fail.');
        } catch (DatabaseException $e) {
            self::assertStringContainsString('has 99 row(s), found 1', $e->getMessage());
        }

        // 3. When pdo_sqlite is loaded (e.g. Linux CI), also exercise real SQLite :memory: SAVEPOINT rollback
        if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $sqlite = PdoConnection::sqlite(':memory:');
            Schema::create($sqlite, 'accounts', static function (Blueprint $table): void {
                $table->id();
                $table->string('email', 160)->unique();
                $table->string('tier', 40)->nullable();
            });
            $sqlite->table('accounts')->insert(['email' => 'seed@kongono.dev', 'tier' => 'pro']);
            $sqHelper = new DatabaseTestHelper($sqlite);
            $sqHelper->inRollbackTransaction(static function (ConnectionInterface $tx) use ($sqHelper): void {
                $tx->table('accounts')->insert(['email' => 'temp@kongono.dev', 'tier' => null]);
                $sqHelper->assertDatabaseCount('accounts', 2);
            });
            $sqHelper->assertDatabaseCount('accounts', 1);
        }
    }

    public function testSoftDeletesBatchLoaderRelationsFactoryAndFullTextSearch(): void
    {
        // 1. Blueprint::softDeletes() & dropSoftDeletes() + QueryBuilder soft-delete SQL & tenant-scope safety
        $bp = new Blueprint('posts', Dialect::Postgres);
        $bp->id();
        $bp->string('title', 160);
        $bp->softDeletes();
        self::assertStringContainsString('"deleted_at" TIMESTAMPTZ NULL', $bp->toSqlStatements()[0]);

        $alterBp = new Blueprint('posts', Dialect::Postgres);
        $alterBp->dropSoftDeletes();
        self::assertSame(['ALTER TABLE "posts" DROP COLUMN "deleted_at"'], $alterBp->toAlterSqlStatements());

        $withoutSql = (new QueryBuilder('posts', Dialect::Postgres))->withoutTrashed()->toSelectSql();
        self::assertStringContainsString('"deleted_at" IS NULL', $withoutSql->query);
        $onlySql = (new QueryBuilder('posts', Dialect::Postgres))->onlyTrashed()->toSelectSql();
        self::assertStringContainsString('"deleted_at" IS NOT NULL', $onlySql->query);

        // 2. BatchLoader::hasMany & belongsTo execute ONE single WHERE IN query (zero N+1) for both arrays & DTOs
        $recordedSql = [];
        $fakeDb = new class ($recordedSql) implements ConnectionInterface {
            /** @param list<string> $sql */
            public function __construct(public array &$sql) {}

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                $this->sql[] = $sql;
                if (str_contains($sql, '"order_items"')) {
                    return [
                        ['id' => 10, 'order_id' => 1, 'sku' => 'SKU-A'],
                        ['id' => 11, 'order_id' => 1, 'sku' => 'SKU-B'],
                        ['id' => 12, 'order_id' => 2, 'sku' => 'SKU-C'],
                    ];
                }
                if (str_contains($sql, '"users"')) {
                    return [
                        ['id' => 100, 'name' => 'Ada'],
                        ['id' => 200, 'name' => 'Grace'],
                    ];
                }

                return [];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                return null;
            }

            public function execute(string $sql, array $bindings = []): int
            {
                $this->sql[] = $sql;

                return 1;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }
        };

        // Tenant-scoped softDelete() & restore() pass through QueryBuilder::update() with tenant isolation intact
        self::assertSame(1, $fakeDb->table('posts')->forTenant(42)->where('id', '=', 7)->softDelete(timestamp: '2026-11-22T00:00:00+00:00'));
        self::assertSame(1, $fakeDb->table('posts')->forTenant(42)->where('id', '=', 7)->restore());
        self::assertSame('UPDATE "posts" SET "deleted_at" = ? WHERE "workspace_id" = ? AND "id" = ?', $recordedSql[0]);

        $orders = [
            (object) ['id' => 1, 'userId' => 100],
            (object) ['id' => 2, 'userId' => 200],
            (object) ['id' => 3, 'userId' => 100],
        ];
        $itemsByOrder = BatchLoader::hasMany($fakeDb, $orders, 'order_items', 'order_id', softDeleteColumn: 'deleted_at');
        self::assertCount(2, $itemsByOrder[1]);
        self::assertCount(1, $itemsByOrder[2]);
        self::assertSame([], $itemsByOrder[3]);

        $usersById = BatchLoader::belongsTo($fakeDb, $orders, 'users', 'user_id', mapper: static fn(array $r): string => (string) ($r['name'] ?? ''));
        self::assertSame(['100' => 'Ada', '200' => 'Grace'], array_combine(array_map('strval', array_keys($usersById)), array_values($usersById)));

        // 3. Factory::for(): count(), sequence(), stackable state(), make(), create()
        $factory = Factory::for('orders', static fn(int $n): array => ['code' => 'ORD-' . $n, 'status' => 'open'])
            ->count(3)
            ->sequence(['tier' => 'free'], ['tier' => 'pro'])
            ->state(['currency' => 'USD']);
        $made = $factory->make();
        self::assertCount(3, $made);
        self::assertSame(['code' => 'ORD-1', 'status' => 'open', 'tier' => 'free', 'currency' => 'USD'], $made[0]);
        self::assertSame(['code' => 'ORD-2', 'status' => 'open', 'tier' => 'pro', 'currency' => 'USD'], $made[1]);
        self::assertSame(['code' => 'ORD-3', 'status' => 'open', 'tier' => 'free', 'currency' => 'USD'], $made[2]);
        $created = $factory->create($fakeDb);
        self::assertCount(3, $created);
        self::assertStringContainsString('INSERT INTO "orders"', end($recordedSql) ?: '');

        // 4. MemorySearchEngine weighted title+body search, metadata filter, snippet, remove
        $memSearch = new MemorySearchEngine();
        $memSearch->index('d1', 'Kongono Security Guide', 'Hardened CSP headers and Argon2id password hashing.', ['section' => 'security']);
        $memSearch->index('d2', 'Queue Operations', 'Redis Lua workers and dead-letter security tips.', ['section' => 'queues']);
        $hits = $memSearch->search('security');
        self::assertCount(2, $hits);
        // Title match d1 outranks body-only match d2
        self::assertSame('d1', $hits[0]->id);
        $filtered = $memSearch->search('security', metadataFilter: ['section' => 'queues']);
        self::assertCount(1, $filtered);
        self::assertSame('d2', $filtered[0]->id);
        $memSearch->remove('d2');
        self::assertCount(1, $memSearch->search('security'));

        // 5. PostgresFullTextSearch SQL compilation + strict language config allowlist
        $pgSqlLog = [];
        $pgSearchConn = new class ($pgSqlLog) implements ConnectionInterface {
            /** @param list<string> $log */
            public function __construct(public array &$log) {}

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                $this->log[] = $sql;

                return [['id' => 'pg-doc-1', 'title' => 'RLS', 'metadata' => '{"env":"prod"}', 'score' => '0.88', 'snippet' => 'PostgreSQL RLS']];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                return null;
            }

            public function execute(string $sql, array $bindings = []): int
            {
                $this->log[] = $sql;

                return 1;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }
        };
        $fts = new PostgresFullTextSearch($pgSearchConn, 'docs_search', 'english');
        $fts->installSchema();
        $fts->index('pg-doc-1', 'RLS', 'PostgreSQL row-level security', ['env' => 'prod']);
        $pgHits = $fts->search('row-level security', limit: 5, metadataFilter: ['env' => 'prod']);
        self::assertCount(1, $pgHits);
        self::assertSame('pg-doc-1', $pgHits[0]->id);
        self::assertSame(0.88, $pgHits[0]->score);
        self::assertStringContainsString('GENERATED ALWAYS AS', $pgSqlLog[0]);
        self::assertStringContainsString('USING GIN (tsv)', $pgSqlLog[1]);
        self::assertStringContainsString('websearch_to_tsquery', $pgSqlLog[4]);

        $this->expectException(DatabaseException::class);
        new PostgresFullTextSearch($pgSearchConn, 'docs_search', "english'; DROP TABLE users;--");
    }

    public function testReadWriteConnectionAndConnectionManagerRoutingAndStickyReset(): void
    {
        $makeSpy = static function (string $label, array &$log): ConnectionInterface {
            return new class ($label, $log) implements ConnectionInterface {
                /** @param list<string> $log */
                public function __construct(
                    private readonly string $label,
                    public array &$log,
                ) {}

                public function dialect(): Dialect
                {
                    return Dialect::Postgres;
                }

                public function query(string $sql, array $bindings = []): array
                {
                    $this->log[] = $this->label . ':query:' . $sql;

                    return [['source' => $this->label]];
                }

                public function first(string $sql, array $bindings = []): ?array
                {
                    $this->log[] = $this->label . ':first:' . $sql;

                    return ['source' => $this->label];
                }

                public function execute(string $sql, array $bindings = []): int
                {
                    $this->log[] = $this->label . ':exec:' . $sql;

                    return 1;
                }

                public function table(string $table, ?string $alias = null): QueryBuilder
                {
                    return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
                }

                public function transaction(Closure $callback): mixed
                {
                    $this->log[] = $this->label . ':tx:begin';
                    $out = $callback($this);
                    $this->log[] = $this->label . ':tx:commit';

                    return $out;
                }
            };
        };

        $log = [];
        $primary = $makeSpy('primary', $log);
        $replica1 = $makeSpy('replica1', $log);
        $replica2 = $makeSpy('replica2', $log);
        $analytics = $makeSpy('analytics', $log);

        $rw = new ReadWriteConnection($primary, [$replica1, $replica2], stickyAfterWrite: true);
        self::assertSame(Dialect::Postgres, $rw->dialect());
        self::assertSame($primary, $rw->writeConnection());
        self::assertCount(2, $rw->readConnections());

        // 1. Pure SELECTs round-robin across read replicas
        self::assertSame('replica1', $rw->table('posts')->get()[0]['source']);
        self::assertSame('replica2', $rw->query('/* comment */ SELECT id FROM posts')[0]['source']);
        self::assertSame('replica1', $rw->first('WITH cte AS (SELECT 1) SELECT * FROM cte')['source'] ?? null);

        // 2. Locking reads (FOR UPDATE) always route to primary WITHOUT flipping sticky
        self::assertSame('primary', $rw->query('SELECT * FROM jobs FOR UPDATE SKIP LOCKED')[0]['source']);
        self::assertFalse($rw->isSticky());

        // 3. Mutating query (INSERT ... RETURNING) routes to primary AND sets sticky-after-write
        self::assertSame('primary', $rw->query('INSERT INTO posts (title) VALUES (?) RETURNING id', ['hi'])[0]['source']);
        self::assertTrue($rw->isSticky());

        // Subsequent SELECTs stay pinned to primary until resetSticky()
        self::assertSame('primary', $rw->query('SELECT * FROM posts')[0]['source']);

        // 4. ConnectionManager supports named connections, lazy closures, and cascades resetSticky()
        $lazyCalls = 0;
        $manager = (new ConnectionManager())
            ->register('default', $rw)
            ->register('analytics', static function () use (&$lazyCalls, $analytics): ConnectionInterface {
                ++$lazyCalls;

                return $analytics;
            });

        self::assertSame(['analytics', 'default'], $manager->names());
        self::assertSame(0, $lazyCalls);
        self::assertSame($analytics, $manager->connection('analytics'));
        self::assertSame($analytics, $manager->connection('analytics'));
        self::assertSame(1, $lazyCalls);
        self::assertSame('analytics', $manager->table('events', 'analytics')->get()[0]['source']);

        // Reset sticky at end of persistent-worker request -> reads return to replicas
        $manager->resetSticky();
        self::assertFalse($rw->isSticky());
        self::assertSame('replica2', $rw->query('SELECT * FROM posts')[0]['source']);

        // Transactions always run on primary and keep sticky enabled
        $txSource = $rw->transaction(static fn(ConnectionInterface $tx): string => (string) ($tx->query('SELECT 1')[0]['source'] ?? ''));
        self::assertSame('primary', $txSource);
        self::assertTrue($rw->isSticky());

        $this->expectException(DatabaseException::class);
        $manager->connection('missing');
    }
}
