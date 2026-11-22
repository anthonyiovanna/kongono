<?php

declare(strict_types=1);

namespace Kongono\Database\Tests;

use Closure;
use Kongono\Database\BatchLoader;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\DatabaseException;
use Kongono\Database\Dialect;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Migrator;
use Kongono\Database\PdoConnection;
use Kongono\Database\QueryBuilder;
use Kongono\Database\QueryGuard;
use Kongono\Database\Repository;
use Kongono\Database\RowLevelSecurity;
use Kongono\Database\Schema\Blueprint;
use Kongono\Database\Schema\ColumnDefinition;
use Kongono\Database\Schema\Schema;
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
}
