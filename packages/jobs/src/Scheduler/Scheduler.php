<?php

declare(strict_types=1);

namespace Kongono\Jobs\Scheduler;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Jobs\Queue;
use Throwable;

/** Deterministic, expressive recurring task scheduler (`kongo schedule:run`). */
final class Scheduler
{
    /** @var list<ScheduledTask> */
    private array $tasks = [];

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
    public function dispatch(Queue $queue, string $jobType, array $payload = [], ?string $queueName = null): ScheduledTask
    {
        return $this->call('job:' . $jobType, static function () use ($queue, $jobType, $payload, $queueName): void {
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
     *
     * @return array{ran: list<string>, failed: array<string, string>}
     */
    public function runDue(?DateTimeInterface $now = null): array
    {
        $ran = [];
        $failed = [];

        foreach ($this->dueTasks($now) as $task) {
            try {
                ($task->callback)();
                $ran[] = $task->name;
            } catch (Throwable $e) {
                $failed[$task->name] = SecretScrubber::scrub($e->getMessage());
            }
        }

        return ['ran' => $ran, 'failed' => $failed];
    }
}
