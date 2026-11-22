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
    ) {}

    /**
     * Start or resume a durable workflow run.
     *
     * @param Closure(WorkflowContext): mixed $definition
     */
    public function run(string $runId, string $workflowName, Closure $definition, ?int $now = null): WorkflowState
    {
        $timestamp = $now ?? time();
        $existing = $this->store->load($runId);

        if (null !== $existing && WorkflowStatus::Completed === $existing->status) {
            return $existing;
        }

        $state = new WorkflowState(
            runId: $runId,
            workflowName: $workflowName,
            status: WorkflowStatus::Running,
            steps: null !== $existing ? $existing->steps : [],
            events: null !== $existing ? $existing->events : [],
            attempts: (null !== $existing ? $existing->attempts : 0) + 1,
        );
        $this->store->save($state);

        $latestSteps = $state->steps;
        $context = new WorkflowContext(
            runId: $runId,
            workflowName: $workflowName,
            initialState: $state,
            now: $timestamp,
            onCheckpoint: function (array $steps) use ($state, &$latestSteps): void {
                $latestSteps = $steps;
                $this->store->save(new WorkflowState(
                    runId: $state->runId,
                    workflowName: $state->workflowName,
                    status: WorkflowStatus::Running,
                    steps: $steps,
                    events: $state->events,
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
                events: $state->events,
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
                events: $state->events,
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
                events: $state->events,
                error: SecretScrubber::scrub($e->getMessage()),
                attempts: $state->attempts,
            );
            $this->store->save($failed);

            return $failed;
        }
    }

    /**
     * Deliver an external event signal (e.g. webhook or human AI approval) to a paused workflow.
     */
    public function signal(string $runId, string $eventName, mixed $payload = true): WorkflowState
    {
        $state = $this->store->load($runId);
        if (null === $state) {
            throw new JobException(sprintf('Workflow run "%s" not found.', $runId));
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
            waitingEvent: $state->waitingEvent === $eventName ? null : $state->waitingEvent,
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
