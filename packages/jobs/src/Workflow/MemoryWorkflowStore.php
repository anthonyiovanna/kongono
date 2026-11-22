<?php

declare(strict_types=1);

namespace Kongono\Jobs\Workflow;

/** In-memory workflow store for fast deterministic unit tests and single-process apps. */
final class MemoryWorkflowStore implements WorkflowStoreInterface
{
    /** @var array<string, WorkflowState> */
    private array $runs = [];

    public function load(string $runId): ?WorkflowState
    {
        return $this->runs[$runId] ?? null;
    }

    public function save(WorkflowState $state): void
    {
        $this->runs[$state->runId] = $state;
    }

    public function delete(string $runId): void
    {
        unset($this->runs[$runId]);
    }
}
