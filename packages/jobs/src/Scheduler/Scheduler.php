<?php

declare(strict_types=1);

namespace Kongono\Jobs\Scheduler;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Kongono\Core\Cache\AtomicCacheInterface;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Jobs\JobException;
use Kongono\Jobs\Queue;
use Throwable;

/** Deterministic, cluster-safe recurring task scheduler (`kongo schedule:run`). */
final class Scheduler
{
    /** @var list<ScheduledTask> */
    private array $tasks = [];

    public function __construct(
        private readonly ?AtomicCacheInterface $lockStore = null,
    ) {}

    /** @param Closure(): void $callback */
    public function call(string $name, Closure $callback): ScheduledTask
    {
        $task = new ScheduledTask($name, $callback);
        $this->tasks[] = $task;

        return $task;
    }

    /**
     * Schedule a recurring background job dispatch onto any Kongono Queue.
     *
     * @param array<string, mixed> $payload
     */
    public function dispatch(Queue $queue, string $jobType, array $payload = [], ?string $queueName = null, ?string $name = null): ScheduledTask
    {
        $taskName = $name ?? ('job:' . $jobType);
        if (null === $name) {
            $existingNames = array_map(static fn(ScheduledTask $t): string => $t->name, $this->tasks);
            if (in_array($taskName, $existingNames, true)) {
                $suffix = 2;
                while (in_array($taskName . '#' . $suffix, $existingNames, true)) {
                    ++$suffix;
                }
                $taskName .= '#' . $suffix;
            }
        }

        return $this->call($taskName, static function () use ($queue, $jobType, $payload, $queueName): void {
            $queue->dispatch($jobType, $payload, $queueName);
        });
    }

    /** @return list<ScheduledTask> */
    public function tasks(): array
    {
        return $this->tasks;
    }

    /** @return list<ScheduledTask> */
    public function dueTasks(?DateTimeInterface $now = null): array
    {
        $moment = $now ?? new DateTimeImmutable('now');

        return array_values(array_filter(
            $this->tasks,
            static fn(ScheduledTask $t): bool => $t->isDue($moment),
        ));
    }

    /**
     * Run all tasks due at $now and return a structured execution report.
     * Tasks configured with `withoutOverlapping()` or `onOneServer()` require an `AtomicCacheInterface`.
     *
     * @return array{ran: list<string>, skipped: list<string>, failed: array<string, string>}
     */
    public function runDue(?DateTimeInterface $now = null, ?AtomicCacheInterface $lockStore = null): array
    {
        $moment = $now ?? new DateTimeImmutable('now');
        $utcMinute = DateTimeImmutable::createFromInterface($moment)->setTimezone(new DateTimeZone('UTC'))->format('YmdHi');
        $store = $lockStore ?? $this->lockStore;
        $ran = [];
        $skipped = [];
        $failed = [];

        foreach ($this->dueTasks($moment) as $task) {
            $needsLock = $task->preventsOverlap() || $task->runsOnOneServer();
            if ($needsLock && null === $store) {
                throw new JobException(sprintf(
                    'Scheduled task "%s" requires an AtomicCacheInterface for withoutOverlapping()/onOneServer() locking.',
                    $task->name,
                ));
            }

            $taskHash = hash('sha256', $task->name);
            $ownerToken = bin2hex(random_bytes(16));
            $overlapKey = null;

            if ($task->preventsOverlap() && null !== $store) {
                $candidateOverlapKey = 'kongo:schedule:overlap:' . $taskHash;
                if (!$store->add($candidateOverlapKey, $ownerToken, $task->overlapTtlSeconds())) {
                    $skipped[] = $task->name;
                    continue;
                }
                $overlapKey = $candidateOverlapKey;
            }

            if ($task->runsOnOneServer() && null !== $store) {
                $serverKey = 'kongo:schedule:oneserver:' . $utcMinute . ':' . $taskHash;
                if (!$store->add($serverKey, $ownerToken, $task->singleServerTtlSeconds())) {
                    if (null !== $overlapKey) {
                        self::releaseOwnedLock($store, $overlapKey, $ownerToken);
                    }
                    $skipped[] = $task->name;
                    continue;
                }
            }

            try {
                ($task->callback)();
                $ran[] = $task->name;
            } catch (Throwable $e) {
                $failed[$task->name] = SecretScrubber::scrub($e->getMessage());
            } finally {
                if (null !== $overlapKey) {
                    try {
                        self::releaseOwnedLock($store, $overlapKey, $ownerToken);
                    } catch (Throwable) {
                        // Best-effort unlock; TTL guarantees lock recovery on transient cache failure
                    }
                }
            }
        }

        return ['ran' => $ran, 'skipped' => $skipped, 'failed' => $failed];
    }

    private static function releaseOwnedLock(AtomicCacheInterface $store, string $key, string $ownerToken): void
    {
        if ($store instanceof \Kongono\Core\Cache\RedisCache) {
            $store->compareAndDelete($key, $ownerToken);

            return;
        }
        if ($store->get($key) === $ownerToken) {
            $store->delete($key);
        }
    }
}
