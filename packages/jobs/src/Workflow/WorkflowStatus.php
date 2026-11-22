<?php

declare(strict_types=1);

namespace Kongono\Jobs\Workflow;

/** Lifecycle state of a durable Kongono workflow execution. */
enum WorkflowStatus: string
{
    case Running = 'running';
    case Sleeping = 'sleeping';
    case WaitingForEvent = 'waiting_for_event';
    case Completed = 'completed';
    case Failed = 'failed';
}
