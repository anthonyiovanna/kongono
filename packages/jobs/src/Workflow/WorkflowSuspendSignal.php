<?php

declare(strict_types=1);

namespace Kongono\Jobs\Workflow;

use RuntimeException;

/**
 * Internal control-flow signal thrown when a workflow cleanly suspends
 * for a durable sleep() or waitForEvent() pause.
 */
final class WorkflowSuspendSignal extends RuntimeException
{
    public function __construct(
        public readonly WorkflowStatus $targetStatus,
        public readonly ?int $wakeAt = null,
        public readonly ?string $waitingEvent = null,
    ) {
        parent::__construct('Workflow suspended: ' . $targetStatus->value);
    }
}
