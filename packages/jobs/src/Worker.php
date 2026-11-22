<?php

declare(strict_types=1);

namespace Kongono\Jobs;

use Closure;
use Kongono\Core\Container;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Core\Kernel;
use Kongono\Core\KernelState;
use Kongono\Jobs\Batch\BatchStoreInterface;
use Kongono\Jobs\Batch\JobBatch;
use Throwable;

/**
 * Persistent-runtime-safe background job worker.
 *
 * - Uses an explicit handler map (never PHP object unserialize())
 * - Supports multi-queue priority draining (`['critical', 'high', 'default']` or `'critical,high,default'`)
 * - Supports composable `JobMiddlewareInterface` pipelines and `JobBatch` fan-out/fan-in tracking
 * - Runs every job inside an isolated Kernel operation scope and resets Container scoped services
 * - Never auto-retries JobOutcome::Uncertain to prevent duplicate non-idempotent side effects
 */
final class Worker
{
    /** @var array<string, JobHandlerInterface|Closure(Job): JobOutcome> */
    private array $handlers = [];

    /** @var list<JobMiddlewareInterface|Closure(Job, Closure(Job): JobOutcome): JobOutcome> */
    private array $middleware = [];

    private ?BatchStoreInterface $batchStore = null;

    /** @var list<Closure(JobBatch): void> */
    private array $batchCompleteHooks = [];

    /** @var list<Closure(JobBatch, Job): void> */
    private array $batchFailureHooks = [];

    /** @var list<Closure(JobBatch): void> */
    private array $batchFinallyHooks = [];

    private bool $daemonStopRequested = false;

    public function __construct(
        private readonly QueueDriverInterface $driver,
        private readonly ?Kernel $kernel = null,
        private readonly ?Container $container = null,
    ) {}

    public function driver(): QueueDriverInterface
    {
        return $this->driver;
    }

    /** @param JobHandlerInterface|Closure(Job): JobOutcome $handler */
    public function register(string $jobType, JobHandlerInterface|Closure $handler): self
    {
        $this->handlers[$jobType] = $handler;

        return $this;
    }

    /**
     * Register one or more composable middleware layers wrapping job execution in FIFO order.
     *
     * @param JobMiddlewareInterface|Closure(Job, Closure(Job): JobOutcome): JobOutcome ...$middleware
     */
    public function middleware(JobMiddlewareInterface|Closure ...$middleware): self
    {
        foreach ($middleware as $mw) {
            $this->middleware[] = $mw;
        }

        return $this;
    }

    /** Attach a {@see BatchStoreInterface} to track parallel {@see JobBatch} progress & cancellation automatically. */
    public function withBatchStore(BatchStoreInterface $store): self
    {
        $this->batchStore = $store;

        return $this;
    }

    /** @param Closure(JobBatch): void $callback Invoked when a batch reaches 0 pending jobs with 0 failures */
    public function onBatchComplete(Closure $callback): self
    {
        $this->batchCompleteHooks[] = $callback;

        return $this;
    }

    /** @param Closure(JobBatch, Job): void $callback Invoked whenever a job in a batch fails permanently */
    public function onBatchFailure(Closure $callback): self
    {
        $this->batchFailureHooks[] = $callback;

        return $this;
    }

    /** @param Closure(JobBatch): void $callback Invoked when a batch finishes (0 pending jobs), regardless of failures */
    public function onBatchFinally(Closure $callback): self
    {
        $this->batchFinallyHooks[] = $callback;

        return $this;
    }

    /**
     * Process up to `$maxJobs` available jobs across one or more priority-ordered queues
     * (pass `'critical,high,default'` or `['critical', 'high', 'default']`).
     *
     * @param string|list<string> $queue
     */
    public function run(
        string|array $queue = 'default',
        int $maxJobs = 50,
        int $leaseSeconds = 60,
        int $baseBackoffSeconds = 5,
        ?int $now = null,
    ): WorkerReport {
        $queues = self::normalizeQueues($queue);
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
            $job = null;
            foreach ($queues as $q) {
                $job = $this->driver->reserve($q, $leaseSeconds, $now);
                if (null !== $job) {
                    break;
                }
            }
            if (null === $job) {
                break;
            }
            ++$processed;

            // Skip jobs belonging to a cancelled batch cleanly without invoking the handler
            $batchId = $job->batchId();
            if (null !== $batchId && null !== $this->batchStore) {
                $batch = $this->batchStore->find($batchId);
                if (null !== $batch && $batch->cancelled) {
                    if ($this->driver->acknowledge($job)) {
                        ++$completed;
                    } else {
                        ++$stale;
                    }
                    continue;
                }
            }

            $outcome = $kernel->runOperation(function () use ($job): array {
                try {
                    $handler = $this->handlers[$job->type] ?? null;
                    if (null === $handler) {
                        return [JobOutcome::Failed, sprintf('No handler registered for job type "%s".', $job->type)];
                    }

                    $core = static fn(Job $j): JobOutcome => $handler instanceof JobHandlerInterface
                        ? $handler->handle($j)
                        : $handler($j);

                    $pipeline = $core;
                    foreach (array_reverse($this->middleware) as $mw) {
                        $next = $pipeline;
                        $pipeline = static fn(Job $j): JobOutcome => $mw instanceof JobMiddlewareInterface
                            ? $mw->handle($j, $next)
                            : $mw($j, $next);
                    }

                    return [$pipeline($job), null];
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
                    $this->recordBatchCompletion($job);
                } else {
                    ++$stale;
                }
            } elseif (JobOutcome::Uncertain === $resultOutcome) {
                // Never blindly retry uncertain mutations; record terminal Failed with clear reason.
                if ($this->driver->markFailed($job, JobStatus::Failed, $error ?? 'Uncertain job outcome; manual inspection required.')) {
                    ++$uncertain;
                    $this->recordBatchFailure($job);
                } else {
                    ++$stale;
                }
            } elseif (JobOutcome::Retry === $resultOutcome && $job->attempt < $job->maxAttempts) {
                $shift = min(20, max(0, $job->attempt - 1));
                $backoff = min(86400, max(0, $baseBackoffSeconds) * (1 << $shift));
                if ($this->driver->releaseForRetry($job, $backoff, $error, $now)) {
                    ++$retried;
                } else {
                    ++$stale;
                }
            } else {
                $terminal = $job->attempt >= $job->maxAttempts ? JobStatus::Dead : JobStatus::Failed;
                if ($this->driver->markFailed($job, $terminal, $error ?? 'Job failed permanently.')) {
                    ++$failed;
                    $this->recordBatchFailure($job);
                } else {
                    ++$stale;
                }
            }
        }

        return new WorkerReport($processed, $completed, $retried, $failed, $uncertain, $stale);
    }

    /**
     * Run a long-lived persistent queue daemon with priority-ordered queue draining,
     * adaptive sleep backoff, periodic pruning of expired terminal jobs, and graceful SIGTERM/SIGINT shutdown.
     *
     * @param string|list<string> $queue
     * @param ?Closure(): bool    $shouldStop Optional external stop predicate (ideal for tests/supervisors)
     */
    public function runDaemon(
        string|array $queue = 'default',
        int $batchSize = 50,
        int $leaseSeconds = 60,
        int $baseBackoffSeconds = 5,
        int $minSleepMs = 50,
        int $maxSleepMs = 1000,
        int $pruneAfterSeconds = 86_400,
        int $pruneIntervalSeconds = 300,
        ?int $maxRuntimeSeconds = null,
        ?int $maxIterations = null,
        ?Closure $shouldStop = null,
    ): WorkerReport {
        $queues = self::normalizeQueues($queue);
        $this->daemonStopRequested = false;
        if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            $onSignal = function (): void {
                $this->daemonStopRequested = true;
            };
            if (defined('SIGTERM')) {
                pcntl_signal(SIGTERM, $onSignal);
            }
            if (defined('SIGINT')) {
                pcntl_signal(SIGINT, $onSignal);
            }
        }

        $safeMinSleep = max(1, $minSleepMs);
        $safeMaxSleep = max($safeMinSleep, $maxSleepMs);
        $sleepMs = $safeMinSleep;
        $startedAt = time();
        $lastPrunedAt = $startedAt;

        $processed = 0;
        $completed = 0;
        $retried = 0;
        $failed = 0;
        $uncertain = 0;
        $stale = 0;
        $iterations = 0;

        while (!$this->daemonStopRequested) {
            if (null !== $shouldStop && $shouldStop()) {
                break;
            }
            if (null !== $maxIterations && $iterations >= $maxIterations) {
                break;
            }
            if (null !== $maxRuntimeSeconds && (time() - $startedAt) >= $maxRuntimeSeconds) {
                break;
            }

            ++$iterations;
            $batch = $this->run($queues, max(1, $batchSize), $leaseSeconds, $baseBackoffSeconds);
            $processed += $batch->processed;
            $completed += $batch->completed;
            $retried += $batch->retried;
            $failed += $batch->failed;
            $uncertain += $batch->uncertain;
            $stale += $batch->stale;

            $now = time();
            if ($pruneIntervalSeconds > 0 && ($now - $lastPrunedAt) >= $pruneIntervalSeconds) {
                foreach ($queues as $q) {
                    $this->driver->prune($q, $now - max(0, $pruneAfterSeconds));
                }
                $lastPrunedAt = $now;
            }

            if ($batch->processed > 0) {
                // Drain immediately when work is flowing
                $sleepMs = $safeMinSleep;
                continue;
            }

            if (null !== $maxIterations && $iterations >= $maxIterations) {
                break;
            }
            if (null !== $shouldStop && $shouldStop()) {
                break;
            }
            usleep($sleepMs * 1000);
            $sleepMs = min($safeMaxSleep, $sleepMs * 2);
        }

        return new WorkerReport($processed, $completed, $retried, $failed, $uncertain, $stale);
    }

    /**
     * Normalize a single queue name, comma-separated priority list (`'high,default,low'`),
     * or array of queue names into a non-empty priority-ordered list.
     *
     * @param string|list<string> $queue
     * @return non-empty-list<string>
     */
    public static function normalizeQueues(string|array $queue): array
    {
        $rawList = is_string($queue) ? explode(',', $queue) : $queue;
        $normalized = [];
        foreach ($rawList as $item) {
            $clean = trim($item);
            if ('' !== $clean && !in_array($clean, $normalized, true)) {
                $normalized[] = $clean;
            }
        }

        return [] !== $normalized ? $normalized : ['default'];
    }

    private function recordBatchCompletion(Job $job): void
    {
        $batchId = $job->batchId();
        if (null === $batchId || null === $this->batchStore) {
            return;
        }

        $updated = $this->batchStore->recordJobCompleted($batchId, $job->id);
        if (null === $updated || 0 !== $updated->pendingJobs) {
            return;
        }

        if ($updated->succeeded()) {
            foreach ($this->batchCompleteHooks as $hook) {
                $hook($updated);
            }
        }
        foreach ($this->batchFinallyHooks as $hook) {
            $hook($updated);
        }
    }

    private function recordBatchFailure(Job $job): void
    {
        $batchId = $job->batchId();
        if (null === $batchId || null === $this->batchStore) {
            return;
        }

        $before = $this->batchStore->find($batchId);
        $wasFinished = null !== $before && $before->finished();

        $updated = $this->batchStore->recordJobFailed($batchId, $job->id);
        if (null === $updated) {
            return;
        }

        foreach ($this->batchFailureHooks as $hook) {
            $hook($updated, $job);
        }
        if (!$wasFinished && $updated->finished()) {
            foreach ($this->batchFinallyHooks as $hook) {
                $hook($updated);
            }
        }
    }
}
