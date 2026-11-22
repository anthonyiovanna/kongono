<?php

declare(strict_types=1);

namespace Kongono\Jobs\Workflow;

/** Persistence contract for durable workflow state snapshots. */
interface WorkflowStoreInterface
{
    public function load(string $runId): ?WorkflowState;

    public function save(WorkflowState $state): void;

    public function delete(string $runId): void;
}
