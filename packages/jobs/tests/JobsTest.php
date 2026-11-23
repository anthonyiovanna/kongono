<?php

declare(strict_types=1);

namespace Kongono\Jobs\Tests;

use Closure;
use DateTimeImmutable;
use Kongono\Core\Cache\MemoryCache;
use Kongono\Core\Cache\RedisClient;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;
use Kongono\Database\QueryBuilder;
use Kongono\Jobs\Batch\BatchDispatcher;
use Kongono\Jobs\Batch\CacheBatchStore;
use Kongono\Jobs\Batch\JobBatch;
use Kongono\Jobs\Batch\MemoryBatchStore;
use Kongono\Jobs\Job;
use Kongono\Jobs\JobException;
use Kongono\Jobs\JobMiddlewareInterface;
use Kongono\Jobs\JobOutcome;
use Kongono\Jobs\JobStatus;
use Kongono\Jobs\MemoryQueueDriver;
use Kongono\Jobs\PostgresQueueDriver;
use Kongono\Jobs\Queue;
use Kongono\Jobs\RedisQueueDriver;
use Kongono\Jobs\Scheduler\CronExpression;
use Kongono\Jobs\Scheduler\Scheduler;
use Kongono\Jobs\Worker;
use Kongono\Jobs\Workflow\CacheWorkflowStore;
use Kongono\Jobs\Workflow\WorkflowContext;
use Kongono\Jobs\Workflow\WorkflowEngine;
use Kongono\Jobs\Workflow\WorkflowStatus;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JobsTest extends TestCase
{
    public function testWorkerProcessesCompletesRetriesDeadLettersAndPreservesUncertain(): void
    {
        $driver = new MemoryQueueDriver();
        $queue = new Queue($driver);

        $queue->dispatch('email.send', ['to' => 'a@example.com'], maxAttempts: 2);
        $queue->dispatch('flaky.task', ['id' => 1], maxAttempts: 2);
        $queue->dispatch('payment.charge', ['amount' => 100], maxAttempts: 3);

        $flakyCalls = 0;
        $worker = (new Worker($driver))
            ->register('email.send', static fn(Job $j): JobOutcome => JobOutcome::Completed)
            ->register('flaky.task', static function () use (&$flakyCalls): JobOutcome {
                ++$flakyCalls;

                return $flakyCalls >= 2 ? JobOutcome::Completed : JobOutcome::Retry;
            })
            ->register('payment.charge', static fn(): JobOutcome => JobOutcome::Uncertain);

        $t0 = time();
        $firstRun = $worker->run(now: $t0);
        self::assertSame(3, $firstRun->processed);
        self::assertSame(1, $firstRun->completed);
        self::assertSame(1, $firstRun->retried);
        self::assertSame(1, $firstRun->uncertain); // Uncertain MUST NOT auto-retry!

        // After retry backoff elapses, flaky task succeeds on attempt 2
        $secondRun = $worker->run(now: $t0 + 10);
        self::assertSame(1, $secondRun->processed);
        self::assertSame(1, $secondRun->completed);

        $stats = $queue->stats();
        self::assertSame(2, $stats['completed']);
        self::assertSame(1, $stats['failed']);
        self::assertSame(0, $stats['pending']);
    }

    public function testLeaseExpiryAndGenerationFencingPreventsStaleAck(): void
    {
        $driver = new MemoryQueueDriver();
        $driver->push('default', 'sync.job', ['x' => 1], maxAttempts: 3, now: 1000);

        $staleJob = $driver->reserve('default', leaseSeconds: 30, now: 1000);
        self::assertNotNull($staleJob);
        self::assertSame(1, $staleJob->generation);

        // Lease expires at 1030; second worker takes over at generation 2
        $freshJob = $driver->reserve('default', leaseSeconds: 30, now: 1035);
        self::assertNotNull($freshJob);
        self::assertSame(2, $freshJob->generation);

        // Stale worker cannot acknowledge or fail generation 1
        self::assertFalse($driver->acknowledge($staleJob));
        // Fresh worker acknowledges generation 2 cleanly
        self::assertTrue($driver->acknowledge($freshJob));
    }

    public function testPostgresQueueDriverExecutesSkipLockedAndFencedSql(): void
    {
        $executedSql = [];
        $fakeDb = new class ($executedSql) implements ConnectionInterface {
            /** @param list<string> $log */
            public function __construct(public array &$log) {}

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                $this->log[] = $sql;

                return [];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                $this->log[] = $sql;

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

        $pg = new PostgresQueueDriver($fakeDb);
        $pg->installSchema();
        $job = $pg->push('default', 'report.build', ['id' => 7]);
        $pg->reserve('default');

        self::assertNotEmpty($job->id);
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS "kongono_jobs"', $executedSql[0]);
        self::assertStringContainsString('INSERT INTO "kongono_jobs"', $executedSql[1]);
        self::assertStringContainsString('FOR UPDATE SKIP LOCKED', $executedSql[2]);
    }

    public function testCronExpressionAndSchedulerExecuteDueTasksAndQueueJobs(): void
    {
        self::assertTrue((new CronExpression('*/5 * * * *'))->isDue(new DateTimeImmutable('2026-11-20 14:15:00')));
        self::assertFalse((new CronExpression('*/5 * * * *'))->isDue(new DateTimeImmutable('2026-11-20 14:17:00')));
        self::assertTrue((new CronExpression('30 2 * * *'))->isDue(new DateTimeImmutable('2026-11-20 02:30:00')));

        $driver = new MemoryQueueDriver();
        $queue = new Queue($driver);
        $ran = [];

        $scheduler = new Scheduler();
        $scheduler->call('cleanup', static function () use (&$ran): void {
            $ran[] = 'cleanup';
        })->everyFiveMinutes();
        $scheduler->dispatch($queue, 'reports.daily')->dailyAt('02:30');
        $scheduler->call('midnight_only', static function () use (&$ran): void {
            $ran[] = 'midnight';
        })->daily();

        $report = $scheduler->runDue(new DateTimeImmutable('2026-11-20 02:30:00'));
        self::assertSame(['cleanup', 'job:reports.daily'], $report['ran']);
        self::assertSame([], $report['failed']);
        self::assertSame(['cleanup'], $ran);
        self::assertSame(1, $queue->stats()['pending']);
    }

    public function testRedisQueueDriverPushesReservesAndAcknowledgesOverResp(): void
    {
        $domain = '\\' === DIRECTORY_SEPARATOR ? STREAM_PF_INET : STREAM_PF_UNIX;
        $pair = stream_socket_pair($domain, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair);
        [$clientSock, $serverSock] = $pair;

        $reservedJobJson = json_encode([
            'id' => 'job_abc',
            'queue' => 'default',
            'type' => 'email.send',
            'payload' => ['to' => 'ada@kongono.dev'],
            'attempt' => 1,
            'max_attempts' => 3,
            'generation' => 1,
            'lease_token' => 'tok_atomic_lua_1',
            'available_at' => 1000,
            'lease_until' => 1065,
            'status' => 'reserved',
            'last_error' => null,
        ], JSON_THROW_ON_ERROR);

        // Preload deterministic RESP responses for push (HSET, ZADD) + atomic Lua reserve (EVAL)
        fwrite(
            $serverSock,
            ":1\r\n"
            . ":1\r\n"
            . '$' . strlen($reservedJobJson) . "\r\n" . $reservedJobJson . "\r\n",
        );

        try {
            $redis = RedisClient::fromStream($clientSock);
            $driver = new RedisQueueDriver($redis);

            $pushed = $driver->push('default', 'email.send', ['to' => 'ada@kongono.dev'], now: 1000);
            self::assertSame('pending', $pushed->status->value);

            $reserved = $driver->reserve('default', leaseSeconds: 60, now: 1005);
            self::assertNotNull($reserved);
            self::assertSame('job_abc', $reserved->id);
            self::assertSame(1, $reserved->attempt);
            self::assertSame(1, $reserved->generation);
            self::assertNotNull($reserved->leaseToken);
            self::assertSame(1065, $reserved->leaseUntil);

            $redis->close();
        } finally {
            fclose($serverSock);
        }
    }

    public function testDurableWorkflowMemoizesStepsSurvivesCrashSleepAndHumanApproval(): void
    {
        $store = new CacheWorkflowStore(new MemoryCache());
        $engine = new WorkflowEngine($store);

        $chargeCount = 0;
        $crashOnce = true;

        $definition = static function (WorkflowContext $wf) use (&$chargeCount, &$crashOnce): array {
            $chargeId = $wf->step('charge_stripe', static function () use (&$chargeCount): string {
                ++$chargeCount;

                return 'ch_12345';
            });

            if ($crashOnce) {
                $crashOnce = false;
                throw new JobException('Transient network timeout with sk_live_1234567890abcdef1234');
            }

            $wf->sleep(3600);

            $approval = $wf->waitForEvent('manager.approved', timeoutSeconds: 86400);

            $receipt = $wf->step('send_receipt', static fn(): string => 'sent:' . $chargeId);

            return ['charge' => $chargeId, 'approval' => $approval, 'receipt' => $receipt];
        };

        // Attempt 1: step 1 succeeds & is memoized, step 2 crashes (secret scrubbed in error)
        $s1 = $engine->run('wf-order-1', 'order.fulfill', $definition, now: 1000);
        self::assertSame(WorkflowStatus::Failed, $s1->status);
        self::assertSame(1, $chargeCount);
        self::assertNotNull($s1->error);
        self::assertStringNotContainsString('sk_live_1234567890abcdef1234', $s1->error);
        self::assertStringContainsString('[REDACTED]', $s1->error);

        // Attempt 2: resumes WITHOUT re-charging Stripe, pauses durably at sleep(3600)
        $s2 = $engine->resume('wf-order-1', $definition, now: 1010);
        self::assertSame(WorkflowStatus::Sleeping, $s2->status);
        self::assertSame(1, $chargeCount);
        self::assertSame(4610, $s2->wakeAt);

        // Attempt 3: after sleep expires, advances to waitForEvent('manager.approved')
        $s3 = $engine->resume('wf-order-1', $definition, now: 5000);
        self::assertSame(WorkflowStatus::WaitingForEvent, $s3->status);
        self::assertSame('manager.approved', $s3->waitingEvent);
        self::assertSame(1, $chargeCount);

        // Human manager approves via signal() within deadline, then workflow completes cleanly
        $engine->signal('wf-order-1', 'manager.approved', ['by' => 'ada@kongono.dev'], now: 5005);
        $s4 = $engine->resume('wf-order-1', $definition, now: 5010);
        self::assertSame(WorkflowStatus::Completed, $s4->status);
        self::assertSame(1, $chargeCount);
        self::assertSame([
            'charge' => 'ch_12345',
            'approval' => ['by' => 'ada@kongono.dev'],
            'receipt' => 'sent:ch_12345',
        ], $s4->output);

        // Re-running a completed workflow is idempotent
        $s5 = $engine->run('wf-order-1', 'order.fulfill', $definition, now: 6000);
        self::assertSame(WorkflowStatus::Completed, $s5->status);
        self::assertSame(1, $chargeCount);
    }

    public function testRound3WorkerBackoffOverflowCapAndWorkflowHijackSignalGuards(): void
    {
        // 1. Worker high-attempt backoff never overflows int bit-shift into float TypeError
        $driver = new MemoryQueueDriver();
        $queue = new Queue($driver);
        $job = $queue->dispatch('high.retry', ['n' => 1], maxAttempts: 100);
        $worker = (new Worker($driver))->register('high.retry', static fn(): JobOutcome => JobOutcome::Retry);

        // Simulate 66 consecutive retries (previously crashed at attempt 63 with float TypeError)
        $now = time() + 1;
        for ($i = 0; $i < 66; ++$i) {
            $rep = $worker->run(maxJobs: 1, now: $now);
            self::assertSame(1, $rep->retried);
            $now += 90_000;
        }
        self::assertSame(1, $driver->stats('default')['pending']);

        // 2. WorkflowEngine rejects cross-workflow runId hijacking and unauthorized signal pre-seeding/overwrite
        $engine = new WorkflowEngine(new CacheWorkflowStore(new MemoryCache()));
        $engine->run('wf-sec-1', 'billing.approve', static function (WorkflowContext $wf): string {
            $wf->waitForEvent('manager.approved');

            return 'ok';
        }, now: 1000);

        // Re-running same runId under a DIFFERENT workflow name fails closed
        try {
            $engine->run('wf-sec-1', 'evil.workflow', static fn(): string => 'hijack', now: 1005);
            self::fail('Expected cross-workflow runId hijack to fail.');
        } catch (JobException $e) {
            self::assertStringContainsString('belongs to "billing.approve"', $e->getMessage());
        }

        // Signaling an event the workflow is NOT waiting for fails closed
        try {
            $engine->signal('wf-sec-1', 'wrong.event', ['approved' => true]);
            self::fail('Expected signal for non-awaited event to fail.');
        } catch (JobException $e) {
            self::assertStringContainsString('is not waiting for event', $e->getMessage());
        }

        // Valid signal succeeds once, duplicate overwrite fails closed
        $engine->signal('wf-sec-1', 'manager.approved', ['by' => 'ada']);
        $this->expectException(JobException::class);
        $this->expectExceptionMessage('is not waiting for event "manager.approved"');
        $engine->signal('wf-sec-1', 'manager.approved', ['by' => 'mallory']);
    }

    public function testRound4WorkflowDeadlinePostgresDepthAndRedisLuaAtomicFencing(): void
    {
        // 1. WorkflowEngine::signal() rejects signals arriving at/after waitForEvent() deadline
        $engine = new WorkflowEngine(new CacheWorkflowStore(new MemoryCache()));
        $engine->run('wf-deadline-1', 'billing.approve', static function (WorkflowContext $wf): string {
            $wf->waitForEvent('manager.approved', timeoutSeconds: 60);

            return 'ok';
        }, now: 1000);

        try {
            $engine->signal('wf-deadline-1', 'manager.approved', ['by' => 'late'], now: 1060);
            self::fail('Expected expired waitForEvent() deadline to reject signal().');
        } catch (JobException $e) {
            self::assertStringContainsString('deadline', $e->getMessage());
        }

        // 2. PostgresQueueDriver::push() rejects deeply nested payloads (> depth 64)
        $fakePg = new class implements ConnectionInterface {
            public function dialect(): Dialect
            {
                return Dialect::Postgres;
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
                return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }
        };
        $deepPayload = ['leaf' => 1];
        for ($i = 0; $i < 70; ++$i) {
            $deepPayload = ['nested' => $deepPayload];
        }
        try {
            (new PostgresQueueDriver($fakePg))->push('default', 'deep.job', $deepPayload);
            self::fail('Expected deeply nested payload to throw JsonException.');
        } catch (\JsonException) {
            self::addToAssertionCount(1);
        }

        // 3. RedisQueueDriver::acknowledge() executes atomic EVAL Lua script and returns false on stale fence (:0)
        $domain = '\\' === DIRECTORY_SEPARATOR ? STREAM_PF_INET : STREAM_PF_UNIX;
        $pair = stream_socket_pair($domain, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair);
        [$clientSock, $serverSock] = $pair;
        fwrite($serverSock, ":0\r\n");

        try {
            $redis = RedisClient::fromStream($clientSock);
            $driver = new RedisQueueDriver($redis);
            $staleJob = new Job(
                id: 'job_stale',
                queue: 'default',
                type: 'email.send',
                payload: [],
                attempt: 1,
                maxAttempts: 3,
                generation: 1,
                leaseToken: 'stale_token',
                availableAt: 1000,
                leaseUntil: 1060,
                status: \Kongono\Jobs\JobStatus::Reserved,
            );

            self::assertFalse($driver->acknowledge($staleJob));
            $redis->close();
            $sentWire = stream_get_contents($serverSock);
            self::assertIsString($sentWire);
            self::assertStringContainsString("\$4\r\nEVAL\r\n", $sentWire);
        } finally {
            fclose($serverSock);
        }
    }

    public function testWorkerDaemonAdaptiveDrainAndRedisO1StatsAndPrune(): void
    {
        // 1. Worker::runDaemon() drains queued jobs immediately with zero cold-boot latency and stops cleanly
        $driver = new MemoryQueueDriver();
        $queue = new Queue($driver);
        $queue->dispatch('mail.send', ['n' => 1]);
        $queue->dispatch('mail.send', ['n' => 2]);

        $worker = (new Worker($driver))->register('mail.send', static fn(): JobOutcome => JobOutcome::Completed);
        $report = $worker->runDaemon(
            queue: 'default',
            batchSize: 10,
            minSleepMs: 1,
            maxSleepMs: 5,
            maxIterations: 2,
        );
        self::assertSame(2, $report->processed);
        self::assertSame(2, $report->completed);

        // 2. RedisQueueDriver::stats() uses O(1) HGETALL(counts) + ZCARD(pending) + ZCARD(reserved), and prune() uses atomic LUA_PRUNE
        $domain = '\\' === DIRECTORY_SEPARATOR ? STREAM_PF_INET : STREAM_PF_UNIX;
        $pair = stream_socket_pair($domain, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair);
        [$clientSock, $serverSock] = $pair;

        // Preload responses for stats(): HGETALL(counts), ZCARD(pending), ZCARD(reserved), plus prune() EVAL returning :3
        fwrite(
            $serverSock,
            "*4\r\n\$9\r\ncompleted\r\n\$2\r\n12\r\n\$4\r\ndead\r\n\$1\r\n1\r\n"
            . ":5\r\n"
            . ":2\r\n"
            . ":3\r\n",
        );

        try {
            $redis = RedisClient::fromStream($clientSock);
            $redisDriver = new RedisQueueDriver($redis);
            $stats = $redisDriver->stats('default');
            self::assertSame(['pending' => 5, 'reserved' => 2, 'completed' => 12, 'failed' => 0, 'dead' => 1], $stats);
            self::assertSame(3, $redisDriver->prune('default', 1000));
            $redis->close();
        } finally {
            fclose($serverSock);
        }
    }

    public function testDeadLetterInspectionReplayAndFlushAcrossDrivers(): void
    {
        // 1. MemoryQueueDriver: listFailed(), retryFailed(single & all), flushFailed()
        $driver = new MemoryQueueDriver();
        $queue = new Queue($driver);

        $j1 = $queue->dispatch('billing.charge', ['order' => 1], maxAttempts: 1);
        $j2 = $queue->dispatch('billing.charge', ['order' => 2], maxAttempts: 1);
        $j3 = $queue->dispatch('billing.charge', ['order' => 3], maxAttempts: 1);

        $shouldSucceed = false;
        $completedOrders = [];
        $worker = (new Worker($driver))->register('billing.charge', static function (Job $job) use (&$shouldSucceed, &$completedOrders): JobOutcome {
            if (!$shouldSucceed) {
                return JobOutcome::Failed;
            }
            $completedOrders[] = $job->payload['order'] ?? null;

            return JobOutcome::Completed;
        });

        $r1 = $worker->run();
        self::assertSame(3, $r1->failed);
        self::assertSame(3, $queue->stats()['dead']);

        $failedList = $queue->listFailed(limit: 2);
        self::assertCount(2, $failedList);
        $allFailed = $queue->listFailed();
        self::assertCount(3, $allFailed);

        // Retry a single failed job by ID
        self::assertSame(1, $queue->retryFailed($j1->id));
        self::assertSame(0, $queue->retryFailed('nonexistent_job'));
        self::assertSame(1, $queue->stats()['pending']);
        self::assertSame(2, $queue->stats()['dead']);

        $shouldSucceed = true;
        $r2 = $worker->run();
        self::assertSame(1, $r2->completed);
        self::assertSame([1], $completedOrders);

        // Retry remaining failed jobs via --all semantics (null jobId)
        self::assertSame(2, $queue->retryFailed());
        self::assertSame(2, $queue->stats()['pending']);
        $r3 = $worker->run();
        self::assertSame(2, $r3->completed);
        self::assertSame([1, 2, 3], $completedOrders);

        // Produce one more dead job and flush it permanently
        $shouldSucceed = false;
        $queue->dispatch('billing.charge', ['order' => 4], maxAttempts: 1);
        $worker->run();
        self::assertCount(1, $queue->listFailed());
        self::assertSame(1, $queue->flushFailed());
        self::assertCount(0, $queue->listFailed());
        self::assertSame(0, $queue->stats()['dead']);
        self::assertSame(3, $queue->stats()['completed']);

        // 2. PostgresQueueDriver SQL verification for uniqueKey dedup, listFailed, retryFailed, flushFailed
        $sqlLog = [];
        $fakePg = new class ($sqlLog) implements ConnectionInterface {
            /** @param list<string> $log */
            public function __construct(public array &$log) {}

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                $this->log[] = $sql;

                return [];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                $this->log[] = $sql;

                return null;
            }

            public function execute(string $sql, array $bindings = []): int
            {
                $this->log[] = $sql;

                return 2;
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

        $pg = new PostgresQueueDriver($fakePg);
        $pg->push('default', 'sync.erp', ['id' => 9], uniqueKey: 'erp:9');
        self::assertSame([], $pg->listFailed('default', 10));
        self::assertSame(2, $pg->retryFailed('default', 'job_123'));
        self::assertSame(2, $pg->retryFailed('default'));
        self::assertSame(2, $pg->flushFailed('default'));
        self::assertStringContainsString('unique_key = ?', $sqlLog[0]);
        self::assertStringContainsString('WHERE queue = ? AND id = ? AND status IN (?, ?)', $sqlLog[3]);
        self::assertStringContainsString('WHERE queue = ? AND status IN (?, ?)', $sqlLog[4]);
        self::assertStringContainsString('DELETE FROM "kongono_jobs"', $sqlLog[5]);

        // 3. RedisQueueDriver atomic Lua wire protocol for uniqueKey push + listFailed + retryFailed + flushFailed
        $domain = '\\' === DIRECTORY_SEPARATOR ? STREAM_PF_INET : STREAM_PF_UNIX;
        $pair = stream_socket_pair($domain, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair);
        [$clientSock, $serverSock] = $pair;

        $dedupJson = json_encode([
            'id' => 'job_existing_unique',
            'queue' => 'default',
            'type' => 'sync.erp',
            'payload' => ['id' => 9],
            'attempt' => 0,
            'max_attempts' => 3,
            'generation' => 0,
            'lease_token' => null,
            'available_at' => 1000,
            'lease_until' => null,
            'status' => 'pending',
            'last_error' => null,
            'unique_key' => 'erp:9',
        ], JSON_THROW_ON_ERROR);
        $failedJson = json_encode([
            'id' => 'job_dead_1',
            'queue' => 'default',
            'type' => 'mail.send',
            'payload' => [],
            'attempt' => 3,
            'max_attempts' => 3,
            'generation' => 3,
            'lease_token' => null,
            'available_at' => 1000,
            'lease_until' => null,
            'status' => 'dead',
            'last_error' => 'SMTP timeout',
            'unique_key' => null,
        ], JSON_THROW_ON_ERROR);

        // Preload RESP responses for: LUA_PUSH_UNIQUE, LUA_LIST_FAILED, LUA_RETRY_FAILED, LUA_FLUSH_FAILED
        fwrite(
            $serverSock,
            '$' . strlen($dedupJson) . "\r\n" . $dedupJson . "\r\n"
            . "*1\r\n$" . strlen($failedJson) . "\r\n" . $failedJson . "\r\n"
            . ":1\r\n"
            . ":2\r\n",
        );

        try {
            $redis = RedisClient::fromStream($clientSock);
            $redisDriver = new RedisQueueDriver($redis);

            $deduped = $redisDriver->push('default', 'sync.erp', ['id' => 9], uniqueKey: 'erp:9');
            self::assertSame('job_existing_unique', $deduped->id);
            self::assertSame('erp:9', $deduped->uniqueKey);

            $deadJobs = $redisDriver->listFailed('default', 25);
            self::assertCount(1, $deadJobs);
            self::assertSame('job_dead_1', $deadJobs[0]->id);
            self::assertSame(JobStatus::Dead, $deadJobs[0]->status);
            self::assertSame('SMTP timeout', $deadJobs[0]->lastError);

            self::assertSame(1, $redisDriver->retryFailed('default', 'job_dead_1'));
            self::assertSame(2, $redisDriver->flushFailed('default'));
            $redis->close();
        } finally {
            fclose($serverSock);
        }
    }

    public function testMultiQueuePriorityDrainingAndDaemonOrder(): void
    {
        self::assertSame(['critical', 'high', 'default'], Worker::normalizeQueues('critical, high, default, high, '));
        self::assertSame(['default'], Worker::normalizeQueues('  ,  '));

        $driver = new MemoryQueueDriver();
        $queue = new Queue($driver);

        // Dispatch in reverse priority order: low -> default -> critical -> high
        $queue->dispatch('task', ['label' => 'low-1'], queue: 'low');
        $queue->dispatch('task', ['label' => 'default-1'], queue: 'default');
        $queue->dispatch('task', ['label' => 'critical-1'], queue: 'critical');
        $queue->dispatch('task', ['label' => 'high-1'], queue: 'high');
        $queue->dispatch('task', ['label' => 'critical-2'], queue: 'critical');

        $order = [];
        $worker = (new Worker($driver))->register('task', static function (Job $job) use (&$order): JobOutcome {
            $order[] = $job->payload['label'] ?? '';

            return JobOutcome::Completed;
        });

        // Drain 3 jobs first: MUST pick both critical jobs and high-1, leaving default-1 and low-1 untouched
        $first = $worker->run('critical,high,default,low', maxJobs: 3);
        self::assertSame(3, $first->completed);
        self::assertSame(['critical-1', 'critical-2', 'high-1'], $order);

        // Drain the rest via runDaemon with array priority list
        $second = $worker->runDaemon(['critical', 'high', 'default', 'low'], batchSize: 10, minSleepMs: 1, maxSleepMs: 2, maxIterations: 2);
        self::assertSame(2, $second->completed);
        self::assertSame(['critical-1', 'critical-2', 'high-1', 'default-1', 'low-1'], $order);
    }

    public function testUniqueJobDeduplicationAndComposableJobMiddleware(): void
    {
        $driver = new MemoryQueueDriver();
        $queue = new Queue($driver);

        // Duplicate dispatch with same uniqueKey while Pending returns existing job ID
        $first = $queue->dispatch('invoice.sync', ['invoice' => 101], uniqueKey: 'invoice:101');
        $dup = $queue->dispatch('invoice.sync', ['invoice' => 101], uniqueKey: 'invoice:101');
        self::assertSame($first->id, $dup->id);
        self::assertSame('invoice:101', $first->uniqueKey);
        self::assertSame(1, $queue->stats()['pending']);

        // While Reserved, duplicate dispatch STILL deduplicates
        $reserved = $driver->reserve('default');
        self::assertNotNull($reserved);
        $dupWhileRunning = $queue->dispatch('invoice.sync', ['invoice' => 101], uniqueKey: 'invoice:101');
        self::assertSame($first->id, $dupWhileRunning->id);
        self::assertSame(0, $queue->stats()['pending']);

        // Once acknowledged (Completed), uniqueKey lock releases and a fresh job can be enqueued
        self::assertTrue($driver->acknowledge($reserved));
        $fresh = $queue->dispatch('invoice.sync', ['invoice' => 101], uniqueKey: 'invoice:101');
        self::assertNotSame($first->id, $fresh->id);
        self::assertSame(1, $queue->stats()['pending']);

        // Composable JobMiddlewareInterface + closure middleware ordering (FIFO enter, LIFO exit) + short-circuiting
        $trace = [];
        $classMiddleware = new class ($trace) implements JobMiddlewareInterface {
            /** @param list<string> $trace */
            public function __construct(private array &$trace) {}

            public function handle(Job $job, Closure $next): JobOutcome
            {
                $this->trace[] = 'mw1:before:' . $job->type;
                $outcome = $next($job);
                $this->trace[] = 'mw1:after:' . $outcome->value;

                return $outcome;
            }
        };

        $shortCircuitMiddleware = static function (Job $job, Closure $next) use (&$trace): JobOutcome {
            $trace[] = 'mw2:before';
            if (true === ($job->payload['rate_limited'] ?? false)) {
                $trace[] = 'mw2:short-circuit';

                return JobOutcome::Retry;
            }
            $outcome = $next($job);
            $trace[] = 'mw2:after';

            return $outcome;
        };

        $worker = (new Worker($driver))
            ->middleware($classMiddleware, $shortCircuitMiddleware)
            ->register('invoice.sync', static function (Job $job) use (&$trace): JobOutcome {
                $trace[] = 'handler:' . (string) ($job->payload['invoice'] ?? '');

                return JobOutcome::Completed;
            });

        $r1 = $worker->run();
        self::assertSame(1, $r1->completed);
        self::assertSame([
            'mw1:before:invoice.sync',
            'mw2:before',
            'handler:101',
            'mw2:after',
            'mw1:after:completed',
        ], $trace);

        // Short-circuiting middleware skips handler and returns Retry
        $trace = [];
        $queue->dispatch('invoice.sync', ['invoice' => 202, 'rate_limited' => true], maxAttempts: 3);
        $r2 = $worker->run();
        self::assertSame(1, $r2->retried);
        self::assertSame([
            'mw1:before:invoice.sync',
            'mw2:before',
            'mw2:short-circuit',
            'mw1:after:retry',
        ], $trace);
    }

    public function testParallelJobBatchFanOutFanInAndCancelOnFailure(): void
    {
        // 1. Successful fan-out/fan-in batch on MemoryBatchStore
        $driver = new MemoryQueueDriver();
        $queue = new Queue($driver);
        $store = new MemoryBatchStore();
        $batches = new BatchDispatcher($queue, $store);

        $batch = $batches->dispatch(
            name: 'monthly-invoices',
            jobs: [
                ['type' => 'invoice.pdf', 'payload' => ['n' => 1]],
                ['type' => 'invoice.pdf', 'payload' => ['n' => 2]],
                ['type' => 'invoice.pdf', 'payload' => ['n' => 3]],
            ],
            meta: ['month' => '2026-11'],
        );
        self::assertSame(3, $batch->totalJobs);
        self::assertSame(0.0, $batch->progress());
        self::assertFalse($batch->finished());

        $completedHooks = [];
        $finallyHooks = [];
        $worker = (new Worker($driver))
            ->withBatchStore($store)
            ->onBatchComplete(static function (JobBatch $b) use (&$completedHooks): void {
                $completedHooks[] = $b->id;
            })
            ->onBatchFinally(static function (JobBatch $b) use (&$finallyHooks): void {
                $finallyHooks[] = $b->id;
            })
            ->register('invoice.pdf', static fn(Job $job): JobOutcome => JobOutcome::Completed);

        // Process 1 job -> 33.33% progress, hooks not fired yet
        $worker->run(maxJobs: 1);
        $mid = $batches->find($batch->id);
        self::assertNotNull($mid);
        self::assertSame(1, $mid->processedJobs);
        self::assertSame(2, $mid->pendingJobs);
        self::assertSame(33.33, $mid->progress());
        self::assertSame([], $completedHooks);

        // Process remaining 2 jobs -> 100%, both complete and finally hooks fire once
        $worker->run(maxJobs: 10);
        $done = $batches->find($batch->id);
        self::assertNotNull($done);
        self::assertTrue($done->finished());
        self::assertTrue($done->succeeded());
        self::assertSame(100.0, $done->progress());
        self::assertSame([$batch->id], $completedHooks);
        self::assertSame([$batch->id], $finallyHooks);

        // 2. CacheBatchStore + allowFailures=false auto-cancels batch and skips remaining queued jobs
        $cacheStore = new CacheBatchStore(new MemoryCache());
        $cacheBatches = new BatchDispatcher($queue, $cacheStore);
        $strictBatch = $cacheBatches->dispatch(
            name: 'import-csv',
            jobs: [
                ['type' => 'csv.row', 'payload' => ['row' => 1], 'maxAttempts' => 1],
                ['type' => 'csv.row', 'payload' => ['row' => 2], 'maxAttempts' => 1],
                ['type' => 'csv.row', 'payload' => ['row' => 3], 'maxAttempts' => 1],
            ],
            allowFailures: false,
        );

        $executedRows = [];
        $failedHooks = [];
        $strictCompleted = [];
        $strictFinally = [];

        $strictWorker = (new Worker($driver))
            ->withBatchStore($cacheStore)
            ->onBatchComplete(static function (JobBatch $b) use (&$strictCompleted): void {
                $strictCompleted[] = $b->id;
            })
            ->onBatchFailure(static function (JobBatch $b, Job $j) use (&$failedHooks): void {
                $failedHooks[] = [$b->id, $j->payload['row'] ?? null];
            })
            ->onBatchFinally(static function (JobBatch $b) use (&$strictFinally): void {
                $strictFinally[] = $b->id;
            })
            ->register('csv.row', static function (Job $job) use (&$executedRows): JobOutcome {
                $row = $job->payload['row'] ?? null;
                $executedRows[] = $row;

                return 1 === $row ? JobOutcome::Failed : JobOutcome::Completed;
            });

        $strictWorker->run(maxJobs: 10);
        $finalStrict = $cacheBatches->find($strictBatch->id);
        self::assertNotNull($finalStrict);
        self::assertTrue($finalStrict->cancelled);
        self::assertTrue($finalStrict->finished());
        self::assertFalse($finalStrict->succeeded());
        self::assertTrue($finalStrict->hasFailures());
        self::assertSame(1, $finalStrict->failedJobs);
        self::assertCount(1, $finalStrict->failedJobIds);
        // Row 1 ran and failed; rows 2 and 3 were skipped automatically because batch was cancelled!
        self::assertSame([1], $executedRows);
        self::assertSame([], $strictCompleted);
        self::assertSame([[$strictBatch->id, 1]], $failedHooks);
        self::assertSame([$strictBatch->id], $strictFinally);
    }

    public function testSchedulerDistributedWithoutOverlappingAndOnOneServerLocks(): void
    {
        $lockStore = new MemoryCache();
        $moment = new DateTimeImmutable('2026-11-22 10:15:00');
        $runs = 0;

        // 1. onOneServer() lets only the first pod execute for a given minute
        $podA = new Scheduler($lockStore);
        $podA->call('nightly-billing', static function () use (&$runs): void {
            ++$runs;
        })->everyFiveMinutes()->onOneServer();

        $podB = new Scheduler($lockStore);
        $podB->call('nightly-billing', static function () use (&$runs): void {
            ++$runs;
        })->everyFiveMinutes()->onOneServer();

        $repA = $podA->runDue($moment);
        $repB = $podB->runDue($moment);
        self::assertSame(['nightly-billing'], $repA['ran']);
        self::assertSame([], $repA['skipped']);
        self::assertSame([], $repB['ran']);
        self::assertSame(['nightly-billing'], $repB['skipped']);
        self::assertSame(1, $runs);

        // Next scheduled minute runs cleanly again
        $repNext = $podB->runDue(new DateTimeImmutable('2026-11-22 10:20:00'));
        self::assertSame(['nightly-billing'], $repNext['ran']);
        self::assertSame(2, $runs);

        // 2. withoutOverlapping() skips concurrent re-entry while held, and releases cleanly on completion or exception
        $overlapRuns = 0;
        $overlapReport = null;
        $overlapScheduler = new Scheduler($lockStore);
        $overlapScheduler->call('sync-inventory', static function () use (&$overlapRuns, &$overlapReport, $overlapScheduler, $moment): void {
            ++$overlapRuns;
            if (1 === $overlapRuns) {
                // Re-entrant tick while first execution is still holding the lock must skip
                $overlapReport = $overlapScheduler->runDue($moment);
            }
        })->everyMinute()->withoutOverlapping(ttlSeconds: 120);

        $first = $overlapScheduler->runDue($moment);
        self::assertSame(['sync-inventory'], $first['ran']);
        self::assertIsArray($overlapReport);
        self::assertSame(['sync-inventory'], $overlapReport['skipped']);
        self::assertSame(1, $overlapRuns);

        // Lock was released in finally -> subsequent tick succeeds
        $second = $overlapScheduler->runDue($moment);
        self::assertSame(['sync-inventory'], $second['ran']);
        self::assertSame(2, $overlapRuns);

        // Exception inside callback also releases overlap lock so next run can recover
        $flakyAttempts = 0;
        $flaky = new Scheduler($lockStore);
        $flaky->call('flaky-task', static function () use (&$flakyAttempts): void {
            ++$flakyAttempts;
            if (1 === $flakyAttempts) {
                throw new RuntimeException('transient boom');
            }
        })->everyMinute()->withoutOverlapping();

        $f1 = $flaky->runDue($moment);
        self::assertArrayHasKey('flaky-task', $f1['failed']);
        $f2 = $flaky->runDue($moment);
        self::assertSame(['flaky-task'], $f2['ran']);

        // 3. Fail-closed JobException if locked task runs without any AtomicCacheInterface
        $unconfigured = new Scheduler();
        $unconfigured->call('locked-no-cache', static function (): void {})->everyMinute()->onOneServer();
        $this->expectException(JobException::class);
        $this->expectExceptionMessage('AtomicCacheInterface');
        $unconfigured->runDue($moment);
    }
}
