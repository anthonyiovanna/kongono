<?php

declare(strict_types=1);

namespace Kongono\Jobs\Tests;

use Closure;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;
use Kongono\Database\QueryBuilder;
use Kongono\Jobs\Job;
use Kongono\Jobs\JobOutcome;
use Kongono\Jobs\MemoryQueueDriver;
use Kongono\Jobs\PostgresQueueDriver;
use Kongono\Jobs\Queue;
use Kongono\Jobs\Worker;
use PHPUnit\Framework\TestCase;

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
}
