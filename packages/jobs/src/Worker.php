<?php

declare(strict_types=1);

namespace Kongono\Jobs;

use Closure;
use Kongono\Core\Container;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Core\Kernel;
use Kongono\Core\KernelState;
use Throwable;

/**
 * Persistent-runtime-safe background job worker.
 *
 * - Uses an explicit handler map (never PHP object unserialize())
 * - Runs every job inside an isolated Kernel operation scope and resets Container scoped services
 * - Never auto-retries JobOutcome::Uncertain to prevent duplicate non-idempotent side effects
 */
final class Worker
{
    /** @var array<string, JobHandlerInterface|Closure(Job): JobOutcome> */
    private array $handlers = [];

    public function __construct(
        private readonly QueueDriverInterface $driver,
        private readonly ?Kernel $kernel = null,
        private readonly ?Container $container = null,
    ) {}

    /** @param JobHandlerInterface|Closure(Job): JobOutcome $handler */
    public function register(string $jobType, JobHandlerInterface|Closure $handler): self
    {
        $this->handlers[$jobType] = $handler;

        return $this;
    }

    /** Process up to $maxJobs available jobs sequentially and return a deterministic summary report. */
    public function run(
        string $queue = 'default',
        int $maxJobs = 50,
        int $leaseSeconds = 60,
        int $baseBackoffSeconds = 5,
        ?int $now = null,
    ): WorkerReport {
        $kernel = $this->kernel ?? new Kernel();
        if (KernelState::Unbooted === $kernel->state()) {
            $kernel->boot();
        }

        $processed = 0;
        $completed = 0;
        $retried = 0;
        $failed = 0;
        $uncertain = 0;
        $stale = 0;

        while ($processed < $maxJobs) {
            $job = $this->driver->reserve($queue, $leaseSeconds, $now);
            if (null === $job) {
                break;
            }
            ++$processed;

            $outcome = $kernel->runOperation(function () use ($job): array {
                try {
                    $handler = $this->handlers[$job->type] ?? null;
                    if (null === $handler) {
                        return [JobOutcome::Failed, sprintf('No handler registered for job type "%s".', $job->type)];
                    }

                    $result = $handler instanceof JobHandlerInterface
                        ? $handler->handle($job)
                        : $handler($job);

                    return [$result, null];
                } catch (Throwable $e) {
                    return [JobOutcome::Retry, SecretScrubber::scrub($e->getMessage())];
                } finally {
                    $this->container?->resetScope();
                }
            }, 'job-' . $job->id);

            [$resultOutcome, $error] = $outcome;

            if (JobOutcome::Completed === $resultOutcome) {
                if ($this->driver->acknowledge($job)) {
                    ++$completed;
                } else {
                    ++$stale;
                }
            } elseif (JobOutcome::Uncertain === $resultOutcome) {
                // Never blindly retry uncertain mutations; record terminal Failed with clear reason.
                if ($this->driver->markFailed($job, JobStatus::Failed, $error ?? 'Uncertain job outcome; manual inspection required.')) {
                    ++$uncertain;
                } else {
                    ++$stale;
                }
            } elseif (JobOutcome::Retry === $resultOutcome && $job->attempt < $job->maxAttempts) {
                $backoff = $baseBackoffSeconds * (2 ** max(0, $job->attempt - 1));
                if ($this->driver->releaseForRetry($job, $backoff, $error, $now)) {
                    ++$retried;
                } else {
                    ++$stale;
                }
            } else {
                $terminal = $job->attempt >= $job->maxAttempts ? JobStatus::Dead : JobStatus::Failed;
                if ($this->driver->markFailed($job, $terminal, $error ?? 'Job failed permanently.')) {
                    ++$failed;
                } else {
                    ++$stale;
                }
            }
        }

        return new WorkerReport($processed, $completed, $retried, $failed, $uncertain, $stale);
    }
}
