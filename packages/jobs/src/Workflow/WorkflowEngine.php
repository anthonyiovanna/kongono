<?php

declare(strict_types=1);

namespace Kongono\Jobs\Workflow;

use Closure;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Jobs\JobException;
use Throwable;

/**
 * Durable multi-step workflow engine (step memoization, durable sleep, external event signals).
 *
 * Works cleanly for long-running SaaS billing lifecycles and human-in-the-loop AI agents
 * without external orchestrators like Temporal or Inngest.
 */
final readonly class WorkflowEngine
{
    public function __construct(
        private WorkflowStoreInterface $store,
        private int $maxAttempts = 100,
    ) {}

    /**
     * Start or resume a durable workflow run.
     *
     * @param Closure(WorkflowContext): mixed $definition
     */
    public function run(string $runId, string $workflowName, Closure $definition, ?int $now = null): WorkflowState
    {
        if ('' === trim($runId) || strlen($runId) > 128 || 1 === preg_match('/[\x00-\x1f\x7f]/', $runId)) {
            throw new JobException('Invalid workflow runId.');
        }

        $timestamp = $now ?? time();
        $existing = $this->store->load($runId);

        // Defend against cross-workflow state hijacking when two workflows collide on a runId
        if (null !== $existing && $existing->workflowName !== $workflowName) {
            throw new JobException(sprintf(
                'Workflow run "%s" belongs to "%s", refusing execution as "%s".',
                $runId,
                $existing->workflowName,
                $workflowName,
            ));
        }

        if (null !== $existing && WorkflowStatus::Completed === $existing->status) {
            return $existing;
        }

        $nextAttempt = (null !== $existing ? $existing->attempts : 0) + 1;
        if ($nextAttempt > $this->maxAttempts) {
            throw new JobException(sprintf('Workflow run "%s" exceeded maximum attempt limit (%d).', $runId, $this->maxAttempts));
        }

        $state = new WorkflowState(
            runId: $runId,
            workflowName: $workflowName,
            status: WorkflowStatus::Running,
            steps: null !== $existing ? $existing->steps : [],
            events: null !== $existing ? $existing->events : [],
            attempts: $nextAttempt,
        );
        $this->store->save($state);

        $latestSteps = $state->steps;
        $mergeEvents = function () use ($runId, $state): array {
            $fresh = $this->store->load($runId);

            return null !== $fresh ? [...$state->events, ...$fresh->events] : $state->events;
        };

        $context = new WorkflowContext(
            runId: $runId,
            workflowName: $workflowName,
            initialState: $state,
            now: $timestamp,
            onCheckpoint: function (array $steps) use ($state, &$latestSteps, $mergeEvents): void {
                $latestSteps = $steps;
                $this->store->save(new WorkflowState(
                    runId: $state->runId,
                    workflowName: $state->workflowName,
                    status: WorkflowStatus::Running,
                    steps: $steps,
                    events: $mergeEvents(),
                    attempts: $state->attempts,
                ));
            },
        );

        try {
            $output = $definition($context);
            $completed = new WorkflowState(
                runId: $runId,
                workflowName: $workflowName,
                status: WorkflowStatus::Completed,
                steps: $context->memoizedSteps(),
                events: $mergeEvents(),
                output: $output,
                attempts: $state->attempts,
            );
            $this->store->save($completed);

            return $completed;
        } catch (WorkflowSuspendSignal $signal) {
            $paused = new WorkflowState(
                runId: $runId,
                workflowName: $workflowName,
                status: $signal->targetStatus,
                steps: $context->memoizedSteps(),
                events: $mergeEvents(),
                wakeAt: $signal->wakeAt,
                waitingEvent: $signal->waitingEvent,
                attempts: $state->attempts,
            );
            $this->store->save($paused);

            return $paused;
        } catch (Throwable $e) {
            $failed = new WorkflowState(
                runId: $runId,
                workflowName: $workflowName,
                status: WorkflowStatus::Failed,
                steps: $latestSteps,
                events: $mergeEvents(),
                error: SecretScrubber::scrub($e->getMessage()),
                attempts: $state->attempts,
            );
            $this->store->save($failed);

            return $failed;
        }
    }

    /**
     * Deliver an external event signal (e.g. webhook or human AI approval) to a workflow currently waiting for it.
     * Refuses terminal runs, expired wait deadlines, duplicate/overwrite signals, and unexpected pre-seeded event names.
     */
    public function signal(string $runId, string $eventName, mixed $payload = true, ?int $now = null): WorkflowState
    {
        $ts = $now ?? time();
        $state = $this->store->load($runId);
        if (null === $state) {
            throw new JobException(sprintf('Workflow run "%s" not found.', $runId));
        }
        if (WorkflowStatus::Completed === $state->status || WorkflowStatus::Failed === $state->status) {
            throw new JobException(sprintf('Cannot signal terminal workflow run "%s" (status: %s).', $runId, $state->status->value));
        }
        if (WorkflowStatus::WaitingForEvent !== $state->status || $state->waitingEvent !== $eventName) {
            throw new JobException(sprintf('Workflow run "%s" is not waiting for event "%s".', $runId, $eventName));
        }
        if (null !== $state->wakeAt && $ts >= $state->wakeAt) {
            throw new JobException(sprintf('Workflow run "%s" wait deadline for event "%s" has expired.', $runId, $eventName));
        }
        if (array_key_exists($eventName, $state->events)) {
            throw new JobException(sprintf('Event "%s" was already delivered to workflow run "%s".', $eventName, $runId));
        }

        $events = $state->events;
        $events[$eventName] = $payload;

        $updated = new WorkflowState(
            runId: $state->runId,
            workflowName: $state->workflowName,
            status: $state->status,
            steps: $state->steps,
            events: $events,
            output: $state->output,
            error: $state->error,
            wakeAt: $state->wakeAt,
            waitingEvent: null,
            attempts: $state->attempts,
        );
        $this->store->save($updated);

        return $updated;
    }

    /**
     * Resume an existing workflow run by ID using its recorded workflow name.
     *
     * @param Closure(WorkflowContext): mixed $definition
     */
    public function resume(string $runId, Closure $definition, ?int $now = null): WorkflowState
    {
        $state = $this->store->load($runId);
        if (null === $state) {
            throw new JobException(sprintf('Workflow run "%s" not found.', $runId));
        }

        return $this->run($runId, $state->workflowName, $definition, $now);
    }

    public function state(string $runId): ?WorkflowState
    {
        return $this->store->load($runId);
    }
}
