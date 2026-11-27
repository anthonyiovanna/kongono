<?php

declare(strict_types=1);

namespace Kongono\Database\Tests;

use Closure;
use Generator;
use Kongono\Database\Bulk\PgCopyWriter;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\DatabaseException;
use Kongono\Database\Dialect;
use Kongono\Database\PdoConnection;
use Kongono\Database\QueryBuilder;
use PDO;
use PHPUnit\Framework\TestCase;

final class PgCopyWriterTest extends TestCase
{
    public function testEncodeCopyLineEscapesControlCharsNullBooleansAndJson(): void
    {
        $line = PgCopyWriter::encodeCopyLine(
            row: [
                'domain' => "acme\texample\nline\r\\end\0",
                'active' => true,
                'deleted' => false,
                'price_cents' => 185000,
                'notes' => null,
                'meta' => ['tld' => 'com', 'bids' => 4],
            ],
            columns: ['domain', 'active', 'deleted', 'price_cents', 'notes', 'meta'],
        );

        self::assertSame(
            'acme\texample\nline\r\\\\end' . "\t" . 't' . "\t" . 'f' . "\t" . '185000' . "\t" . '\N' . "\t" . '{"tld":"com","bids":4}',
            $line,
        );
    }

    public function testBuildPostgresMergeSqlIncludesDistinctFromGuardAndDoNothingVariant(): void
    {
        $stageSql = PgCopyWriter::buildPostgresStageTableSql(
            table: 'public.domains',
            stageTable: 'kgn_stage_abc123',
            columns: ['domain', 'price_cents', 'status'],
        );
        self::assertSame(
            'CREATE TEMP TABLE "kgn_stage_abc123" ON COMMIT DROP AS SELECT "domain", "price_cents", "status" FROM "public"."domains" WHERE false',
            $stageSql,
        );

        $dedupedSql = PgCopyWriter::buildPostgresMergeSql(
            table: 'public.domains',
            stageTable: 'kgn_stage_abc123',
            columns: ['domain', 'price_cents', 'status'],
            conflictColumns: ['domain'],
            updateColumns: ['price_cents', 'status'],
            skipUnchanged: true,
            deduplicate: true,
        );

        self::assertSame(
            'INSERT INTO "public"."domains" ("domain", "price_cents", "status") '
            . 'SELECT DISTINCT ON ("domain") "domain", "price_cents", "status" FROM "kgn_stage_abc123" ORDER BY "domain", ctid DESC '
            . 'ON CONFLICT ("domain") DO UPDATE SET "price_cents" = EXCLUDED."price_cents", "status" = EXCLUDED."status" '
            . 'WHERE ("public"."domains"."price_cents" IS DISTINCT FROM EXCLUDED."price_cents" OR "public"."domains"."status" IS DISTINCT FROM EXCLUDED."status")',
            $dedupedSql,
        );

        $rawMergeSql = PgCopyWriter::buildPostgresMergeSql(
            table: 'public.domains',
            stageTable: 'kgn_stage_abc123',
            columns: ['domain', 'price_cents', 'status'],
            conflictColumns: ['domain'],
            updateColumns: ['price_cents', 'status'],
            skipUnchanged: true,
            deduplicate: false,
        );
        self::assertStringContainsString('SELECT "domain", "price_cents", "status" FROM "kgn_stage_abc123" ON CONFLICT', $rawMergeSql);

        $doNothingSql = PgCopyWriter::buildPostgresMergeSql(
            table: 'domains',
            stageTable: 'kgn_stage_abc123',
            columns: ['domain', 'price_cents'],
            conflictColumns: ['domain'],
            updateColumns: [],
        );

        self::assertSame(
            'INSERT INTO "domains" ("domain", "price_cents") SELECT "domain", "price_cents" FROM "kgn_stage_abc123" ON CONFLICT ("domain") DO NOTHING',
            $doNothingSql,
        );
    }

    public function testStreamingInsertAndUpsertChunkingAcrossDialects(): void
    {
        // 1. Streaming Generator insert into 50-row chunks inside a single transaction
        $pgSpy = new BulkSpyConnection(Dialect::Postgres);
        $pgWriter = new PgCopyWriter($pgSpy);

        $gen = static function (): Generator {
            for ($i = 1; $i <= 120; $i++) {
                yield [
                    'domain' => sprintf('example-%03d.com', $i),
                    'price_cents' => 1000 + $i,
                    'status' => 'active',
                ];
            }
        };

        $ins = $pgWriter->insert('domains', ['domain', 'price_cents', 'status'], $gen(), chunkSize: 50);
        self::assertSame(120, $ins->rowsProcessed);
        self::assertSame(120, $ins->rowsAffected);
        self::assertSame(3, $ins->chunks);
        self::assertSame(1, $pgSpy->transactions);
        self::assertCount(3, $pgSpy->executed);
        self::assertStringStartsWith('INSERT INTO "domains" ("domain", "price_cents", "status") VALUES ', $pgSpy->executed[0]['sql']);
        self::assertCount(150, $pgSpy->executed[0]['bindings']); // 50 rows * 3 cols
        self::assertCount(60, $pgSpy->executed[2]['bindings']);  // 20 rows * 3 cols

        // 2. Postgres fallback upsert deduplicates duplicate keys (last-row-wins) & emits IS DISTINCT FROM guard
        $pgSpy->executed = [];
        $up = $pgWriter->upsert(
            table: 'domains',
            columns: ['domain', 'price_cents', 'status'],
            conflictColumns: ['domain'],
            rows: [
                ['domain' => 'a.com', 'price_cents' => 1000, 'status' => 'pending'],
                ['domain' => 'a.com', 'price_cents' => 4500, 'status' => 'auction'],
                ['domain' => 'b.com', 'price_cents' => 9900, 'status' => 'dropping'],
            ],
            skipUnchanged: true,
            deduplicate: true,
        );
        self::assertSame(3, $up->rowsProcessed);
        self::assertSame(1, $up->chunks);
        self::assertCount(6, $pgSpy->executed[0]['bindings']); // 2 deduped rows * 3 cols
        self::assertSame(['a.com', 4500, 'auction', 'b.com', 9900, 'dropping'], $pgSpy->executed[0]['bindings']);
        self::assertStringContainsString('ON CONFLICT ("domain") DO UPDATE SET', $pgSpy->executed[0]['sql']);
        self::assertStringContainsString('IS DISTINCT FROM excluded."price_cents"', $pgSpy->executed[0]['sql']);

        // 3. DO NOTHING mode
        $pgSpy->executed = [];
        $pgWriter->upsert(
            table: 'domains',
            columns: ['domain', 'price_cents'],
            conflictColumns: ['domain'],
            rows: [['domain' => 'a.com', 'price_cents' => 1]],
            updateColumns: [],
        );
        self::assertStringContainsString('ON CONFLICT ("domain") DO NOTHING', $pgSpy->executed[0]['sql']);

        // 4. MySQL dialect emits backtick-quoted ON DUPLICATE KEY UPDATE
        $mysqlSpy = new BulkSpyConnection(Dialect::Mysql);
        (new PgCopyWriter($mysqlSpy))->upsert(
            table: 'domains',
            columns: ['domain', 'price_cents'],
            conflictColumns: ['domain'],
            rows: [['domain' => 'a.com', 'price_cents' => 5000]],
        );
        self::assertStringContainsString('INSERT INTO `domains` (`domain`, `price_cents`)', $mysqlSpy->executed[0]['sql']);
        self::assertStringContainsString('ON DUPLICATE KEY UPDATE `price_cents` = VALUES(`price_cents`)', $mysqlSpy->executed[0]['sql']);

        // 5. Real SQLite in-memory verification when pdo_sqlite is enabled in PHP runtime
        if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $db = PdoConnection::sqlite(':memory:');
            $db->execute('CREATE TABLE domains (domain TEXT PRIMARY KEY, price_cents INTEGER NOT NULL, status TEXT NOT NULL)');
            $sqliteWriter = new PgCopyWriter($db);
            $sqliteWriter->insert('domains', ['domain', 'price_cents', 'status'], [
                ['domain' => 'a.com', 'price_cents' => 1000, 'status' => 'active'],
                ['domain' => 'b.com', 'price_cents' => 2000, 'status' => 'active'],
            ]);
            $noop = $sqliteWriter->upsert(
                table: 'domains',
                columns: ['domain', 'price_cents', 'status'],
                conflictColumns: ['domain'],
                rows: [['domain' => 'a.com', 'price_cents' => 1000, 'status' => 'active']],
                skipUnchanged: true,
            );
            self::assertSame(0, $noop->rowsAffected);

            $changed = $sqliteWriter->upsert(
                table: 'domains',
                columns: ['domain', 'price_cents', 'status'],
                conflictColumns: ['domain'],
                rows: [
                    ['domain' => 'a.com', 'price_cents' => 1000, 'status' => 'active'],
                    ['domain' => 'b.com', 'price_cents' => 4500, 'status' => 'auction'],
                ],
                skipUnchanged: true,
            );
            self::assertSame(1, $changed->rowsAffected);
        }
    }

    public function testRejectsUnsafeIdentifiersAndInvalidConfig(): void
    {
        $writer = new PgCopyWriter(new BulkSpyConnection(Dialect::Postgres));

        $this->expectException(DatabaseException::class);
        $writer->insert('domains; DROP TABLE users--', ['domain'], [['domain' => 'a.com']]);
    }
}

final class BulkSpyConnection implements ConnectionInterface
{
    /** @var list<array{sql: string, bindings: list<mixed>}> */
    public array $executed = [];

    public int $transactions = 0;

    public function __construct(
        private readonly Dialect $dialect,
    ) {}

    public function dialect(): Dialect
    {
        return $this->dialect;
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
        $this->executed[] = ['sql' => $sql, 'bindings' => $bindings];

        // Count row placeholders `(? ...)` in multi-row INSERT/UPSERT statements
        return max(1, substr_count($sql, '(') - substr_count($sql, 'ON CONFLICT') - 1);
    }

    public function table(string $table, ?string $alias = null): QueryBuilder
    {
        return new QueryBuilder($table, $this->dialect, $this, $alias);
    }

    public function transaction(Closure $callback): mixed
    {
        $this->transactions++;

        return $callback($this);
    }
}
