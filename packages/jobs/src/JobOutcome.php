<?php

declare(strict_types=1);

namespace Kongono\Jobs;

enum JobOutcome: string
{
    /** Work completed cleanly and may be acknowledged. */
    case Completed = 'completed';

    /** Retryable failure; re-enqueue with backoff if attempts remain. */
    case Retry = 'retry';

    /** Permanent failure; mark dead/failed without retry. */
    case Failed = 'failed';

    /** Ambiguous post-effect state; do NOT silently retry non-idempotent work. */
    case Uncertain = 'uncertain';
}
