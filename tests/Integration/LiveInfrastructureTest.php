<?php

declare(strict_types=1);

namespace Kongono\Tests\Integration;

use Kongono\Ai\Embedding\PgVectorStore;
use Kongono\Ai\Embedding\VectorDocument;
use Kongono\Core\Cache\RedisCache;
use Kongono\Core\Cache\RedisClient;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\DatabaseException;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Migrator;
use Kongono\Database\PdoConnection;
use Kongono\Database\QueryBuilder;
use Kongono\Database\RowLevelSecurity;
use Kongono\Database\Schema\Blueprint;
use Kongono\Database\Schema\Schema;
use Kongono\Jobs\Job;
use Kongono\Jobs\JobOutcome;
use Kongono\Jobs\PostgresQueueDriver;
use Kongono\Jobs\Queue;
use Kongono\Jobs\RedisQueueDriver;
use Kongono\Jobs\Worker;
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
        $redisWorker2->close();
        $redis->close();
    }
}
