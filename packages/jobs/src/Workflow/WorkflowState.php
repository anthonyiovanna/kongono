<?php

declare(strict_types=1);

namespace Kongono\Jobs\Workflow;

/**
 * Immutable persisted snapshot of a durable workflow run.
 *
 * Stores memoized step results, received external signals, sleep wake deadlines,
 * and final output/error so a workflow can survive crashes, restarts, and multi-day waits.
 */
final readonly class WorkflowState
{
    /**
     * @param array<string, mixed> $steps  Memoized outputs keyed by step name
     * @param array<string, mixed> $events Delivered external signals keyed by event name
     */
    public function __construct(
        public string $runId,
        public string $workflowName,
        public WorkflowStatus $status,
        public array $steps = [],
        public array $events = [],
        public mixed $output = null,
        public ?string $error = null,
        public ?int $wakeAt = null,
        public ?string $waitingEvent = null,
        public int $attempts = 0,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'run_id' => $this->runId,
            'workflow_name' => $this->workflowName,
            'status' => $this->status->value,
            'steps' => $this->steps,
            'events' => $this->events,
            'output' => $this->output,
            'error' => $this->error,
            'wake_at' => $this->wakeAt,
            'waiting_event' => $this->waitingEvent,
            'attempts' => $this->attempts,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $rawStatus = is_string($data['status'] ?? null) ? $data['status'] : WorkflowStatus::Running->value;
        $status = WorkflowStatus::tryFrom($rawStatus) ?? WorkflowStatus::Running;

        /** @var array<string, mixed> $steps */
        $steps = is_array($data['steps'] ?? null) ? $data['steps'] : [];
        /** @var array<string, mixed> $events */
        $events = is_array($data['events'] ?? null) ? $data['events'] : [];

        return new self(
            runId: is_string($data['run_id'] ?? null) ? $data['run_id'] : '',
            workflowName: is_string($data['workflow_name'] ?? null) ? $data['workflow_name'] : '',
            status: $status,
            steps: $steps,
            events: $events,
            output: $data['output'] ?? null,
            error: is_string($data['error'] ?? null) ? $data['error'] : null,
            wakeAt: is_int($data['wake_at'] ?? null) ? $data['wake_at'] : null,
            waitingEvent: is_string($data['waiting_event'] ?? null) ? $data['waiting_event'] : null,
            attempts: is_int($data['attempts'] ?? null) ? $data['attempts'] : 0,
        );
    }
}
