<?php

declare(strict_types=1);

namespace Kongono\Tests\Integration;

use Kongono\Ai\Embedding\HybridSearch;
use Kongono\Ai\Embedding\PgVectorStore;
use Kongono\Ai\Embedding\VectorDocument;
use Kongono\Core\Cache\RedisCache;
use Kongono\Core\Cache\RedisClient;
use Kongono\Database\BatchLoader;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\DatabaseException;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Migrator;
use Kongono\Database\NonTransactionalMigrationInterface;
use Kongono\Database\PdoConnection;
use Kongono\Database\QueryBuilder;
use Kongono\Database\RowLevelSecurity;
use Kongono\Database\Schema\Blueprint;
use Kongono\Database\Schema\Schema;
use Kongono\Database\Search\PostgresFullTextSearch;
use Kongono\Database\Testing\DatabaseTestHelper;
use Kongono\Database\Testing\Factory;
use Kongono\Jobs\Batch\BatchDispatcher;
use Kongono\Jobs\Batch\CacheBatchStore;
use Kongono\Jobs\Batch\JobBatch;
use Kongono\Jobs\Job;
use Kongono\Jobs\JobOutcome;
use Kongono\Jobs\PostgresQueueDriver;
use Kongono\Jobs\Queue;
use Kongono\Jobs\RedisQueueDriver;
use Kongono\Jobs\Scheduler\Scheduler;
use Kongono\Jobs\Worker;
use Kongono\Jobs\Workflow\PdoWorkflowStore;
use Kongono\Jobs\Workflow\WorkflowContext;
use Kongono\Jobs\Workflow\WorkflowEngine;
use Kongono\Jobs\Workflow\WorkflowStatus;
use Kongono\Security\Auth\CachePersonalAccessTokenStore;
use Kongono\Security\Auth\LoginThrottler;
use Kongono\Security\Auth\PasswordResetBroker;
use Kongono\Security\Auth\PdoPersonalAccessTokenStore;
use Kongono\Security\Auth\RememberMeManager;
use Kongono\Security\Principal;
use Kongono\Security\PrincipalKind;
use PHPUnit\Framework\TestCase;

/**
 * Live infrastructure integration tests executed in CI against real PostgreSQL (pgvector)
 * and Redis/Valkey service containers.
 */
final class LiveInfrastructureTest extends TestCase
{
    public function testLivePostgresQueryBuilderMigrationsQueueAndPgVector(): void
    {
        $url = getenv('KONGONO_TEST_POSTGRES_URL');
        if (false === $url || '' === $url) {
            self::markTestSkipped('Set KONGONO_TEST_POSTGRES_URL to run live PostgreSQL + pgvector tests.');
        }

        $db = PdoConnection::fromUrl($url);
        $queries = [];
        $db->onQuery(static function (string $sql, float $ms) use (&$queries): void {
            $queries[] = [$sql, $ms];
        });

        // 1. Migrator migrate + Schema::create + QueryBuilder upsert & whereGroup + rollback
        $migration = new class implements MigrationInterface {
            public function id(): string
            {
                return '20261121_integration_items';
            }

            public function up(ConnectionInterface $conn): void
            {
                Schema::create($conn, 'integration_items', static function (Blueprint $table): void {
                    $table->id();
                    $table->string('slug', 100)->unique();
                    $table->string('title', 200);
                    $table->integer('score')->default(0);
                });
            }

            public function down(ConnectionInterface $conn): void
            {
                Schema::dropIfExists($conn, 'integration_items');
            }
        };

        $migrator = new Migrator($db, 'kongono_integration_migrations');
        $db->execute('DROP TABLE IF EXISTS "integration_items"');
        $db->execute('DROP TABLE IF EXISTS "kongono_integration_migrations"');

        self::assertSame(['20261121_integration_items'], $migrator->migrate([$migration]));

        $db->table('integration_items')->upsert(
            [
                ['slug' => 'alpha', 'title' => 'Alpha', 'score' => 10],
                ['slug' => 'beta', 'title' => 'Beta', 'score' => 50],
            ],
            ['slug'],
            ['title', 'score'],
        );
        $db->table('integration_items')->upsert(
            [['slug' => 'alpha', 'title' => 'Alpha Updated', 'score' => 99]],
            ['slug'],
            ['title', 'score'],
        );

        $rows = $db->table('integration_items')
            ->whereGroup(static function (QueryBuilder $q): void {
                $q->where('slug', '=', 'alpha')->orWhere('score', '>=', 50);
            })
            ->orderBy('score', 'DESC')
            ->get();

        self::assertCount(2, $rows);
        self::assertSame('Alpha Updated', $rows[0]['title']);
        self::assertNotEmpty($queries);

        self::assertSame(['20261121_integration_items'], $migrator->rollback([$migration], steps: 1));

        // 1b. v1.11.0: Blueprint uuid/json/decimal/foreignId, Schema::table() + CONCURRENTLY index, Migrator::pretend() & fresh()
        $mAccounts = new class implements MigrationInterface {
            public function id(): string
            {
                return '20261122_01_accounts';
            }

            public function up(ConnectionInterface $conn): void
            {
                Schema::create($conn, 'integration_accounts', static function (Blueprint $table): void {
                    $table->id();
                    $table->uuid('public_id')->unique();
                    $table->decimal('balance', 14, 2)->default(0);
                    $table->json('prefs')->default('{}');
                });
                Schema::create($conn, 'integration_ledger', static function (Blueprint $connTable): void {
                    $connTable->id();
                    $connTable->foreignId('account_id', 'integration_accounts', 'id', onDelete: 'CASCADE');
                    $connTable->decimal('delta', 12, 2);
                    $connTable->string('memo', 120)->nullable();
                });
            }

            public function down(ConnectionInterface $conn): void
            {
                Schema::dropIfExists($conn, 'integration_ledger');
                Schema::dropIfExists($conn, 'integration_accounts');
            }
        };

        $mAlterConcurrent = new class implements NonTransactionalMigrationInterface {
            public function id(): string
            {
                return '20261122_02_alter_ledger_concurrent';
            }

            public function up(ConnectionInterface $conn): void
            {
                Schema::table($conn, 'integration_ledger', static function (Blueprint $table): void {
                    $table->string('currency', 3)->default('USD');
                    $table->renameColumn('memo', 'note');
                    $table->index(['account_id', 'currency'], concurrently: true);
                });
            }

            public function down(ConnectionInterface $conn): void
            {
                Schema::table($conn, 'integration_ledger', static function (Blueprint $table): void {
                    $table->dropIndex(['account_id', 'currency'], concurrently: true);
                    $table->renameColumn('note', 'memo');
                    $table->dropColumn('currency');
                });
            }
        };

        $schemaMigrator = new Migrator($db, 'kongono_schema_v111_migrations');
        $freshResult = $schemaMigrator->fresh([$mAccounts, $mAlterConcurrent]);
        self::assertSame(['20261122_01_accounts', '20261122_02_alter_ledger_concurrent'], $freshResult['applied']);

        // Verify live PostgreSQL UUID, JSONB, NUMERIC, FOREIGN KEY CASCADE, and renamed column work end-to-end
        $acctIdRow = $db->table('integration_accounts')->insertReturning([
            'public_id' => '550e8400-e29b-41d4-a716-446655440000',
            'balance' => '1250.75',
            'prefs' => '{"tier":"pro"}',
        ], ['id']);
        $rawAcctId = $acctIdRow['id'] ?? null;
        $acctId = is_int($rawAcctId) ? $rawAcctId : (is_numeric($rawAcctId) ? (int) $rawAcctId : 0);
        self::assertGreaterThan(0, $acctId);

        $db->table('integration_ledger')->insert([
            'account_id' => $acctId,
            'delta' => '49.99',
            'note' => 'Initial credit',
        ]);
        $ledgerRow = $db->table('integration_ledger')->where('account_id', '=', $acctId)->first();
        self::assertNotNull($ledgerRow);
        self::assertSame('USD', $ledgerRow['currency']);
        self::assertSame('Initial credit', $ledgerRow['note']);

        // ON DELETE CASCADE cleans up child ledger row automatically
        $db->table('integration_accounts')->where('id', '=', $acctId)->delete();
        self::assertSame(0, $db->table('integration_ledger')->where('account_id', '=', $acctId)->count());

        // Rollback non-transactional CONCURRENTLY migration + verify pretend() previews ONLY the pending one
        self::assertSame(['20261122_02_alter_ledger_concurrent'], $schemaMigrator->rollback([$mAccounts, $mAlterConcurrent], steps: 1));
        $pretendPreview = $schemaMigrator->pretend([$mAccounts, $mAlterConcurrent]);
        self::assertSame(['20261122_02_alter_ledger_concurrent'], array_keys($pretendPreview));
        self::assertContains(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS "idx_integration_ledger_account_id_currency" ON "integration_ledger" ("account_id", "currency")',
            $pretendPreview['20261122_02_alter_ledger_concurrent'],
        );

        // 1c. v1.15.0 live PostgreSQL 16 DatabaseTestHelper (rollback isolation + nested SAVEPOINT + DB assertions)
        $dbHelper = new DatabaseTestHelper($db);
        $dbHelper->assertDatabaseCount('integration_accounts', 0);
        $dbHelper->inRollbackTransaction(static function (ConnectionInterface $tx) use ($dbHelper): void {
            $tx->table('integration_accounts')->insert([
                'public_id' => '550e8400-e29b-41d4-a716-446655440001',
                'balance' => '200.00',
                'prefs' => '{}',
            ]);
            $dbHelper->assertDatabaseHas('integration_accounts', ['public_id' => '550e8400-e29b-41d4-a716-446655440001']);

            // Nested transaction uses real PostgreSQL SAVEPOINT cleanly
            $tx->transaction(static function (ConnectionInterface $inner): void {
                $inner->table('integration_accounts')->insert([
                    'public_id' => '550e8400-e29b-41d4-a716-446655440002',
                    'balance' => '300.00',
                    'prefs' => '{}',
                ]);
            });
            $dbHelper->assertDatabaseCount('integration_accounts', 2);
        });
        $dbHelper
            ->assertDatabaseCount('integration_accounts', 0)
            ->assertDatabaseMissing('integration_accounts', ['public_id' => '550e8400-e29b-41d4-a716-446655440001']);

        // 2. Live PostgresQueueDriver (FOR UPDATE SKIP LOCKED + fencing) + Worker
        $db->execute('DROP TABLE IF EXISTS "kongono_integration_jobs"');
        $pgQueue = new PostgresQueueDriver($db, 'kongono_integration_jobs');
        $pgQueue->installSchema();
        $queue = new Queue($pgQueue);
        $queue->dispatch('demo.ping', ['ok' => true]);

        $ranPayload = null;
        $worker = (new Worker($pgQueue))
            ->register('demo.ping', static function (Job $job) use (&$ranPayload): JobOutcome {
                $ranPayload = $job->payload;

                return JobOutcome::Completed;
            });

        $report = $worker->run();
        self::assertSame(1, $report->completed);
        self::assertSame(['ok' => true], $ranPayload);

        // 2b. v1.14.0 live PostgresQueueDriver uniqueKey deduplication + dead-letter inspection/replay/flush
        $u1 = $queue->dispatch('demo.ping', ['ok' => true], uniqueKey: 'pg-unique-1');
        $u2 = $queue->dispatch('demo.ping', ['ok' => true], uniqueKey: 'pg-unique-1');
        self::assertSame($u1->id, $u2->id);
        self::assertSame(1, $pgQueue->stats('default')['pending']);
        self::assertSame(1, $worker->run()->completed);

        // Dead-letter a failing job, list, retry, and flush
        $failJob = $queue->dispatch('demo.always_fail', ['x' => 1], maxAttempts: 1);
        $failWorker = (new Worker($pgQueue))->register('demo.always_fail', static fn(): JobOutcome => JobOutcome::Failed);
        self::assertSame(1, $failWorker->run()->failed);
        $pgFailed = $pgQueue->listFailed('default');
        self::assertCount(1, $pgFailed);
        self::assertSame($failJob->id, $pgFailed[0]->id);
        self::assertSame(1, $pgQueue->retryFailed('default', $failJob->id));
        self::assertSame(1, $failWorker->run()->failed);
        self::assertSame(1, $pgQueue->flushFailed('default'));
        self::assertSame([], $pgQueue->listFailed('default'));

        // 2c. v1.23.0 live PdoWorkflowStore (durable PostgreSQL sleep + wakeup + external signal)
        $db->execute('DROP TABLE IF EXISTS "kongono_integration_workflows"');
        $wfStore = new PdoWorkflowStore($db, 'kongono_integration_workflows');
        $wfStore->installSchema();
        $wfDef = static function (WorkflowContext $wf): array {
            $step = $wf->step('init', static fn(): string => 'initialized');
            $wf->sleep(3600, 'cooldown');
            $sig = $wf->waitForEvent('approved', timeoutSeconds: 7200);

            return ['step' => $step, 'sig' => $sig];
        };
        $wfEngine = new WorkflowEngine($wfStore);
        $s1 = $wfEngine->run('pg-wf-1', 'LivePgWorkflow', $wfDef, now: 1_700_000_000);
        self::assertSame(WorkflowStatus::Sleeping, $s1->status);
        self::assertCount(0, $wfStore->listDueWakeups(now: 1_700_003_599));
        self::assertCount(1, $wfStore->listDueWakeups(now: 1_700_003_600));
        $s2 = $wfEngine->run('pg-wf-1', 'LivePgWorkflow', $wfDef, now: 1_700_003_600);
        self::assertSame(WorkflowStatus::WaitingForEvent, $s2->status);
        self::assertCount(1, $wfStore->listWaitingForEvent('approved', 'LivePgWorkflow'));
        $wfEngine->signal('pg-wf-1', 'approved', ['ok' => true], now: 1_700_003_700);
        $s3 = $wfEngine->run('pg-wf-1', 'LivePgWorkflow', $wfDef, now: 1_700_003_705);
        self::assertSame(WorkflowStatus::Completed, $s3->status);
        self::assertSame(['step' => 'initialized', 'sig' => ['ok' => true]], $s3->output);

        // 3. Live PgVectorStore (real pgvector <=> cosine similarity + JSONB @> filter)
        $db->execute('DROP TABLE IF EXISTS "kongono_integration_vectors"');
        $pgv = new PgVectorStore($db, 'kongono_integration_vectors');
        $pgv->ensureTable(3);
        $pgv->upsert(new VectorDocument('v1', 'PHP 8.5 framework', [1.0, 0.0, 0.0], ['lang' => 'php']));
        $pgv->upsert(new VectorDocument('v2', 'Python script', [0.0, 1.0, 0.0], ['lang' => 'python']));

        $hits = $pgv->search([0.99, 0.01, 0.0], limit: 2, metadataFilter: ['lang' => 'php']);
        self::assertCount(1, $hits);
        self::assertSame('v1', $hits[0]->document->id);
        self::assertGreaterThan(0.9, $hits[0]->score);

        // 4. Live PostgreSQL Row-Level Security (FORCE ROW LEVEL SECURITY + transaction-local set_config)
        $db->execute('DROP TABLE IF EXISTS "integration_rls_notes"');
        Schema::create($db, 'integration_rls_notes', static function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('workspace_id');
            $table->string('title', 200);
            $table->rowLevelSecurity('workspace_id');
        });
        $db->execute("DO \$\$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'kongono_rls_app') THEN CREATE ROLE kongono_rls_app NOSUPERUSER NOCREATEDB NOCREATEROLE NOBYPASSRLS; END IF; END \$\$;");
        $db->execute('GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE "integration_rls_notes" TO kongono_rls_app');
        $db->execute('GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO kongono_rls_app');

        RowLevelSecurity::withTenant($db, 101, static function (ConnectionInterface $tx): void {
            $tx->table('integration_rls_notes')->insert(['workspace_id' => 101, 'title' => 'Tenant 101 Secret']);
        });
        RowLevelSecurity::withTenant($db, 202, static function (ConnectionInterface $tx): void {
            $tx->table('integration_rls_notes')->insert(['workspace_id' => 202, 'title' => 'Tenant 202 Secret']);
        });

        // Unscoped non-superuser session fails closed with ZERO rows visible
        $unscopedRows = $db->transaction(static function (ConnectionInterface $tx): array {
            $tx->execute('SET LOCAL ROLE kongono_rls_app');

            return $tx->query('SELECT * FROM "integration_rls_notes"');
        });
        self::assertSame([], $unscopedRows);

        // Scoped to tenant 101: sees ONLY tenant 101's row
        $t101Rows = RowLevelSecurity::withTenant($db, 101, static function (ConnectionInterface $tx): array {
            $tx->execute('SET LOCAL ROLE kongono_rls_app');

            return $tx->query('SELECT title FROM "integration_rls_notes"');
        });
        self::assertSame([['title' => 'Tenant 101 Secret']], $t101Rows);

        // Cross-tenant INSERT as tenant 101 is blocked by PostgreSQL RLS WITH CHECK policy
        try {
            RowLevelSecurity::withTenant($db, 101, static function (ConnectionInterface $tx): void {
                $tx->execute('SET LOCAL ROLE kongono_rls_app');
                $tx->table('integration_rls_notes')->insert(['workspace_id' => 202, 'title' => 'Forged']);
            });
            self::fail('Expected PostgreSQL RLS WITH CHECK policy to block cross-tenant insert.');
        } catch (DatabaseException) {
            self::addToAssertionCount(1);
        }

        // After commit, transaction-local set_config(..., true) MUST have cleared automatically (persistent-runtime safe!)
        $postCommitRows = $db->transaction(static function (ConnectionInterface $tx): array {
            $tx->execute('SET LOCAL ROLE kongono_rls_app');

            return $tx->query('SELECT * FROM "integration_rls_notes"');
        });
        self::assertSame([], $postCommitRows);

        // 5. v1.16.0 live PostgreSQL 16 PdoPersonalAccessTokenStore (installSchema, issue, authenticate + lastUsedAt, list, revoke)
        $db->execute('DROP TABLE IF EXISTS "kongono_integration_tokens"');
        $patStore = new PdoPersonalAccessTokenStore($db, 'kongono_integration_tokens');
        $patStore->installSchema();

        $patActor = new Principal('pg-user-1', PrincipalKind::Human, ['billing.read', 'billing.write']);
        $issuedPat = $patStore->issue($patActor, 'PG CI Token', capabilities: ['billing.read'], ttlSeconds: 3600);
        self::assertStringStartsWith('kgn_pat_', $issuedPat->plainTextToken);

        $authedPat = $patStore->authenticate($issuedPat->plainTextToken);
        self::assertNotNull($authedPat);
        self::assertSame('pg-user-1', $authedPat->principalId);
        self::assertSame(['billing.read'], $authedPat->capabilities);
        self::assertNotNull($authedPat->lastUsedAt);
        self::assertCount(1, $patStore->listForPrincipal('pg-user-1'));

        self::assertTrue($patStore->revoke($issuedPat->record->id));
        self::assertNull($patStore->authenticate($issuedPat->plainTextToken));

        // 6. v1.17.0 live PostgreSQL 16: softDeletes + Factory + BatchLoader::hasMany/belongsTo + PostgresFullTextSearch + HybridSearch
        $db->execute('DROP TABLE IF EXISTS "integration_v117_items"');
        $db->execute('DROP TABLE IF EXISTS "integration_v117_orders"');
        Schema::create($db, 'integration_v117_orders', static function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->softDeletes();
        });
        Schema::create($db, 'integration_v117_items', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id', 'integration_v117_orders', 'id', onDelete: 'CASCADE');
            $table->string('sku', 64);
            $table->softDeletes();
        });

        Factory::for('integration_v117_orders', static fn(int $n): array => ['code' => 'ORD-' . $n])
            ->count(2)
            ->create($db);
        $liveOrders = $db->table('integration_v117_orders')->withoutTrashed()->orderBy('id', 'ASC')->get();
        self::assertCount(2, $liveOrders);
        $firstOrderId = is_numeric($liveOrders[0]['id'] ?? null) ? (int) $liveOrders[0]['id'] : 0;
        self::assertGreaterThan(0, $firstOrderId);

        Factory::for('integration_v117_items', static fn(int $n): array => ['order_id' => $firstOrderId, 'sku' => 'SKU-' . $n])
            ->count(2)
            ->create($db);

        // Soft delete + onlyTrashed + restore
        self::assertSame(1, $db->table('integration_v117_orders')->where('id', '=', $firstOrderId)->softDelete());
        self::assertSame(1, $db->table('integration_v117_orders')->withoutTrashed()->count());
        self::assertSame(1, $db->table('integration_v117_orders')->onlyTrashed()->count());
        self::assertSame(1, $db->table('integration_v117_orders')->where('id', '=', $firstOrderId)->restore());
        self::assertSame(2, $db->table('integration_v117_orders')->withoutTrashed()->count());

        // Zero-N+1 eager hasMany + belongsTo
        $itemsByOrder = BatchLoader::hasMany($db, $liveOrders, 'integration_v117_items', 'order_id', softDeleteColumn: 'deleted_at');
        self::assertCount(2, $itemsByOrder[$firstOrderId]);
        $allItems = $db->table('integration_v117_items')->get();
        $parentOrders = BatchLoader::belongsTo($db, $allItems, 'integration_v117_orders', 'order_id');
        self::assertArrayHasKey($firstOrderId, $parentOrders);

        // Live PostgreSQL Full-Text Search (tsvector + GIN + websearch_to_tsquery + ts_rank_cd + ts_headline) + HybridSearch RRF with PgVectorStore
        $db->execute('DROP TABLE IF EXISTS "kongono_integration_fts"');
        $fts = new PostgresFullTextSearch($db, 'kongono_integration_fts', 'english');
        $fts->installSchema();
        $fts->index('v1', 'PHP 8.5 Framework Guide', 'Kongono delivers pure PHP 8.5 framework performance and security.', ['lang' => 'php']);
        $fts->index('v2', 'Python Scripting', 'Legacy python utility script notes.', ['lang' => 'python']);

        $ftsHits = $fts->search('PHP framework', limit: 5, metadataFilter: ['lang' => 'php']);
        self::assertCount(1, $ftsHits);
        self::assertSame('v1', $ftsHits[0]->id);
        self::assertGreaterThan(0.0, $ftsHits[0]->score);
        self::assertNotSame('', $ftsHits[0]->snippet);

        $hybrid = new HybridSearch($fts, $pgv);
        $hybridHits = $hybrid->search('PHP framework', [0.99, 0.01, 0.0], limit: 2, metadataFilter: ['lang' => 'php']);
        self::assertCount(1, $hybridHits);
        self::assertSame('v1', $hybridHits[0]->id);
        self::assertNotNull($hybridHits[0]->keywordScore);
        self::assertNotNull($hybridHits[0]->vectorScore);
    }

    public function testLiveRedisCacheAndRedisQueueDriver(): void
    {
        $url = getenv('KONGONO_TEST_REDIS_URL');
        if (false === $url || '' === $url) {
            self::markTestSkipped('Set KONGONO_TEST_REDIS_URL to run live Redis/Valkey integration tests.');
        }

        $redis = RedisClient::fromUrl($url);
        self::assertSame('PONG', $redis->ping());

        $cache = new RedisCache($redis, 'kongono:inttest:cache:');
        $cache->clear();
        $cache->set('greeting', ['msg' => 'hello redis'], ttlSeconds: 60);
        self::assertTrue($cache->has('greeting'));
        self::assertSame(['msg' => 'hello redis'], $cache->get('greeting'));
        $cache->delete('greeting');
        self::assertFalse($cache->has('greeting'));

        $driver = new RedisQueueDriver($redis, 'kongono:inttest:queue:');
        $queue = new Queue($driver);
        $queue->dispatch('redis.job', ['n' => 42]);

        $seen = null;
        $worker = (new Worker($driver))
            ->register('redis.job', static function (Job $job) use (&$seen): JobOutcome {
                $seen = $job->payload['n'] ?? null;

                return JobOutcome::Completed;
            });

        $report = $worker->run();
        self::assertSame(1, $report->completed);
        self::assertSame(42, $seen);
        self::assertSame(1, $driver->stats('default')['completed']);
        self::assertGreaterThanOrEqual(1, $driver->prune('default', time() + 60));
        self::assertSame(0, $driver->stats('default')['completed']);

        // v1.14.0 live Redis 7: atomic Lua uniqueKey dedup, dead-letter replay/flush, priority queues, & CacheBatchStore
        $rUnique1 = $queue->dispatch('redis.job', ['n' => 77], uniqueKey: 'redis-uniq-77');
        $rUnique2 = $queue->dispatch('redis.job', ['n' => 77], uniqueKey: 'redis-uniq-77');
        self::assertSame($rUnique1->id, $rUnique2->id);
        self::assertSame(1, $driver->stats('default')['pending']);
        self::assertSame(1, $worker->run()->completed);
        // After completion, uniqueKey lock is freed atomically by LUA_TRANSITION
        $rUnique3 = $queue->dispatch('redis.job', ['n' => 88], uniqueKey: 'redis-uniq-77');
        self::assertNotSame($rUnique1->id, $rUnique3->id);
        self::assertSame(1, $worker->run()->completed);

        // Live Redis dead-letter inspection, retry, and flush
        $deadRedisJob = $queue->dispatch('redis.fail', ['x' => 1], maxAttempts: 1);
        $failRedisWorker = (new Worker($driver))->register('redis.fail', static fn(): JobOutcome => JobOutcome::Failed);
        self::assertSame(1, $failRedisWorker->run()->failed);
        $redisFailed = $driver->listFailed('default');
        self::assertCount(1, $redisFailed);
        self::assertSame($deadRedisJob->id, $redisFailed[0]->id);
        self::assertSame(1, $driver->retryFailed('default', $deadRedisJob->id));
        self::assertSame(1, $failRedisWorker->run()->failed);
        self::assertSame(1, $driver->flushFailed('default'));
        self::assertSame([], $driver->listFailed('default'));

        // Live Redis multi-queue priority draining + CacheBatchStore fan-out/fan-in
        $batchStore = new CacheBatchStore($cache, 'kongono:inttest:batch:');
        $batches = new BatchDispatcher($queue, $batchStore);
        $batch = $batches->dispatch('redis-batch', [
            ['type' => 'batch.item', 'payload' => ['i' => 1]],
            ['type' => 'batch.item', 'payload' => ['i' => 2]],
        ], queue: 'critical');
        $queue->dispatch('batch.item', ['i' => 99], queue: 'default');

        $seenOrder = [];
        $batchFinishedId = null;
        $prioWorker = (new Worker($driver))
            ->withBatchStore($batchStore)
            ->onBatchComplete(static function (JobBatch $b) use (&$batchFinishedId): void {
                $batchFinishedId = $b->id;
            })
            ->register('batch.item', static function (Job $job) use (&$seenOrder): JobOutcome {
                $seenOrder[] = $job->payload['i'] ?? null;

                return JobOutcome::Completed;
            });

        self::assertSame(3, $prioWorker->run('critical,default')->completed);
        // Both critical-queue batch items strictly preempt default-queue item 99
        // (within the same second, Redis ZSET breaks equal-score ties lexicographically by job ID).
        self::assertCount(3, $seenOrder);
        self::assertSame(99, $seenOrder[2]);
        $criticalItems = [$seenOrder[0], $seenOrder[1]];
        sort($criticalItems);
        self::assertSame([1, 2], $criticalItems);
        self::assertSame($batch->id, $batchFinishedId);
        $finalBatch = $batches->find($batch->id);
        self::assertNotNull($finalBatch);
        self::assertTrue($finalBatch->succeeded());
        self::assertSame(100.0, $finalBatch->progress());
        $driver->prune('critical', time() + 60);
        $driver->prune('default', time() + 60);

        // Live distributed RedisEventBus across two independent worker clients + Last-Event-ID replay
        $redisWorker2 = RedisClient::fromUrl($url);
        $busWorker1 = new \Kongono\Http\Realtime\RedisEventBus($redis, 'kongono:inttest:sse:');
        $busWorker2 = new \Kongono\Http\Realtime\RedisEventBus($redisWorker2, 'kongono:inttest:sse:');
        $busWorker1->clear();

        $e1 = $busWorker1->publish('room-1', 'msg', ['text' => 'hello from worker 1']);
        $e2 = $busWorker1->publish('room-1', 'msg', ['text' => 'second event']);
        $allOnWorker2 = $busWorker2->since('room-1');
        self::assertCount(2, $allOnWorker2);
        self::assertSame($e1->id, $allOnWorker2[0]->id);

        $missed = $busWorker2->since('room-1', $e1->id);
        self::assertCount(1, $missed);
        self::assertSame($e2->id, $missed[0]->id);
        self::assertStringContainsString('second event', $missed[0]->data);

        $busWorker1->clear();

        // v1.16.0 live Redis 7 auth suite: CachePersonalAccessTokenStore, PasswordResetBroker, LoginThrottler, RememberMeManager
        $redisPat = new CachePersonalAccessTokenStore($cache, 'kongono:inttest:pat:');
        $redisUser = new Principal('redis-user-1', PrincipalKind::Human, ['api.read']);
        $redisIssued = $redisPat->issue($redisUser, 'Redis Key', ttlSeconds: 300);
        self::assertNotNull($redisPat->authenticate($redisIssued->plainTextToken));
        self::assertTrue($redisPat->revoke($redisIssued->record->id));
        self::assertNull($redisPat->authenticate($redisIssued->plainTextToken));

        $redisReset = new PasswordResetBroker($cache, 'kongono:inttest:reset:');
        $rTok = $redisReset->createToken('redis@kongono.dev', ttlSeconds: 300);
        self::assertTrue($redisReset->consume('redis@kongono.dev', $rTok));
        self::assertFalse($redisReset->consume('redis@kongono.dev', $rTok));

        $redisThrottle = new LoginThrottler($cache, maxAttempts: 2, lockoutSeconds: 60);
        $redisThrottle->recordFailure('redis@kongono.dev', '203.0.113.5');
        $redisThrottle->recordFailure('redis@kongono.dev', '203.0.113.5');
        self::assertTrue($redisThrottle->isLockedOut('redis@kongono.dev', '203.0.113.5'));
        $redisThrottle->clear('redis@kongono.dev', '203.0.113.5');
        self::assertFalse($redisThrottle->isLockedOut('redis@kongono.dev', '203.0.113.5'));

        $redisRemember = new RememberMeManager($cache, prefix: 'kongono:inttest:rem:');
        $c1 = $redisRemember->issue($redisUser, ttlSeconds: 300);
        $rot = $redisRemember->consumeAndRotate($c1->value);
        self::assertNotNull($rot);
        // Replaying old cookie revokes rotated cookie too!
        self::assertNull($redisRemember->consumeAndRotate($c1->value));
        self::assertNull($redisRemember->consumeAndRotate($rot['cookie']->value));

        // Live Redis 7 distributed Scheduler onOneServer() + withoutOverlapping()
        $schedMoment = new \DateTimeImmutable('2026-11-22 12:30:00');
        $schedRuns = 0;
        $pod1 = new Scheduler($cache);
        $pod1->call('redis-nightly-job', static function () use (&$schedRuns): void {
            ++$schedRuns;
        })->everyFiveMinutes()->onOneServer()->withoutOverlapping();
        $pod2 = new Scheduler($cache);
        $pod2->call('redis-nightly-job', static function () use (&$schedRuns): void {
            ++$schedRuns;
        })->everyFiveMinutes()->onOneServer()->withoutOverlapping();

        self::assertSame(['redis-nightly-job'], $pod1->runDue($schedMoment)['ran']);
        self::assertSame(['redis-nightly-job'], $pod2->runDue($schedMoment)['skipped']);
        self::assertSame(1, $schedRuns);

        $redisWorker2->close();
        $redis->close();
    }

    public function testLiveMysqlQueueUniqueKeyDeadLetterReplayAndPersonalAccessTokens(): void
    {
        $url = getenv('KONGONO_TEST_MYSQL_URL');
        if (false === $url || '' === $url) {
            self::markTestSkipped('Set KONGONO_TEST_MYSQL_URL to run live MySQL 8 integration tests.');
        }

        $db = PdoConnection::fromUrl($url);
        self::assertSame(\Kongono\Database\Dialect::Mysql, $db->dialect());

        // 1. Live MySQL 8 PostgresQueueDriver schema + unique_key failed-job deduplication (single & bulk retry)
        $db->execute('DROP TABLE IF EXISTS `kongono_mysql_jobs`');
        $mysqlQueue = new PostgresQueueDriver($db, 'kongono_mysql_jobs');
        $mysqlQueue->installSchema();
        $queue = new Queue($mysqlQueue);

        $failWorker = (new Worker($mysqlQueue))->register('mysql.fail', static fn(): JobOutcome => JobOutcome::Failed);

        // Two failed jobs with the same unique_key (`order-1`): bulk retryFailed() must reactivate ONLY 1 (newest)
        $j1 = $queue->dispatch('mysql.fail', ['v' => 1], maxAttempts: 1, uniqueKey: 'order-1');
        self::assertSame(1, $failWorker->run()->failed);
        $j2 = $queue->dispatch('mysql.fail', ['v' => 2], maxAttempts: 1, uniqueKey: 'order-1');
        self::assertSame(1, $failWorker->run()->failed);
        self::assertNotSame($j1->id, $j2->id);

        // One failed job + one currently active pending job sharing unique_key (`order-2`): retryFailed() must NOT reactivate the failed one!
        $j3 = $queue->dispatch('mysql.fail', ['v' => 3], maxAttempts: 1, uniqueKey: 'order-2');
        self::assertSame(1, $failWorker->run()->failed);
        $queue->dispatch('mysql.fail', ['v' => 4], maxAttempts: 1, uniqueKey: 'order-2');

        // Single-job retry on `order-2` must be blocked (0 updated) because an active pending job exists
        self::assertSame(0, $mysqlQueue->retryFailed('default', $j3->id));
        // Bulk retry must reactivate strictly 1 job (`order-1` latest) and leave `order-2`'s failed copy alone
        self::assertSame(1, $mysqlQueue->retryFailed('default'));
        $stats = $mysqlQueue->stats('default');
        self::assertSame(2, $stats['pending']); // 1 active `order-2` + 1 reactivated `order-1`

        // 2. Live MySQL 8 PdoPersonalAccessTokenStore inline index schema + issue/authenticate/revokeAll
        $db->execute('DROP TABLE IF EXISTS `kongono_mysql_pats`');
        $patStore = new PdoPersonalAccessTokenStore($db, 'kongono_mysql_pats');
        $patStore->installSchema();
        $user = new Principal('mysql-user-1', PrincipalKind::Human, ['api.read']);
        $issued = $patStore->issue($user, 'MySQL CI Token', ttlSeconds: 300);
        self::assertNotNull($patStore->authenticate($issued->plainTextToken));
        self::assertSame(1, $patStore->revokeAllForPrincipal('mysql-user-1'));
        self::assertNull($patStore->authenticate($issued->plainTextToken));

        $db->execute('DROP TABLE IF EXISTS `kongono_mysql_jobs`');
        $db->execute('DROP TABLE IF EXISTS `kongono_mysql_pats`');
    }
}
