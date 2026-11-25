<?php

declare(strict_types=1);

namespace Kongono\Jobs\Workflow;

use Closure;
use Kongono\Jobs\JobException;

/**
 * Execution context passed to durable workflow definitions.
 *
 * Every `$ctx->step()` call is idempotently memoized: if the workflow crashes or sleeps
 * and resumes later, completed steps return their persisted value without re-executing.
 */
final class WorkflowContext
{
    /** @var array<string, mixed> */
    private array $steps;

    /** @var array<string, mixed> */
    private array $events;

    private int $sleepCounter = 0;

    /**
     * @param Closure(array<string, mixed>): void $onCheckpoint
     */
    public function __construct(
        public readonly string $runId,
        public readonly string $workflowName,
        WorkflowState $initialState,
        private readonly int $now,
        private readonly Closure $onCheckpoint,
    ) {
        $this->steps = $initialState->steps;
        $this->events = $initialState->events;
    }

    /**
     * Execute an idempotent, memoized workflow step.
     *
     * @template T
     * @param Closure(): T $fn
     * @return T
     */
    public function step(string $name, Closure $fn): mixed
    {
        if ('' === trim($name) || str_starts_with($name, '__')) {
            throw new JobException(sprintf('Invalid workflow step name "%s": cannot be empty or start with reserved "__" prefix.', $name));
        }
        if (array_key_exists($name, $this->steps)) {
            /** @var T $cached */
            $cached = $this->steps[$name];

            return $cached;
        }

        $result = $fn();
        $this->steps[$name] = $result;
        ($this->onCheckpoint)($this->steps);

        return $result;
    }

    /**
     * Durably sleep for `$seconds` (survives worker restarts without holding a PHP thread).
     */
    public function sleep(int $seconds, ?string $stepName = null): void
    {
        if (null !== $stepName && ('' === trim($stepName) || str_starts_with($stepName, '__'))) {
            throw new JobException(sprintf('Invalid workflow sleep step name "%s": cannot be empty or start with reserved "__" prefix.', $stepName));
        }
        ++$this->sleepCounter;
        $key = $stepName ?? '__sleep_' . $this->sleepCounter;

        $wakeAt = array_key_exists($key, $this->steps) && is_int($this->steps[$key])
            ? $this->steps[$key]
            : $this->now + max(1, $seconds);

        if (!array_key_exists($key, $this->steps)) {
            $this->steps[$key] = $wakeAt;
            ($this->onCheckpoint)($this->steps);
        }

        if ($this->now < $wakeAt) {
            throw new WorkflowSuspendSignal(
                targetStatus: WorkflowStatus::Sleeping,
                wakeAt: $wakeAt,
            );
        }
    }

    /**
     * Durably pause until an external event (webhook, human AI approval, etc.) is delivered
     * via `WorkflowEngine::signal($runId, $eventName, $payload)`.
     */
    public function waitForEvent(string $eventName, ?int $timeoutSeconds = null): mixed
    {
        if (array_key_exists($eventName, $this->events)) {
            return $this->events[$eventName];
        }

        $deadlineKey = '__event_deadline_' . $eventName;
        $deadline = null;
        if (null !== $timeoutSeconds && $timeoutSeconds > 0) {
            if (array_key_exists($deadlineKey, $this->steps) && is_int($this->steps[$deadlineKey])) {
                $deadline = $this->steps[$deadlineKey];
            } else {
                $deadline = $this->now + $timeoutSeconds;
                $this->steps[$deadlineKey] = $deadline;
                ($this->onCheckpoint)($this->steps);
            }

            if ($this->now >= $deadline) {
                throw new JobException(sprintf('Workflow "%s" timed out waiting for event "%s".', $this->runId, $eventName));
            }
        }

        throw new WorkflowSuspendSignal(
            targetStatus: WorkflowStatus::WaitingForEvent,
            wakeAt: $deadline,
            waitingEvent: $eventName,
        );
    }

    /** @return array<string, mixed> */
    public function memoizedSteps(): array
    {
        return $this->steps;
    }
}
