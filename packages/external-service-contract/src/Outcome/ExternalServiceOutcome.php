<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Outcome;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies bounded operation outcome claims without external-effect truth. */
#[Api(ApiStatus::Experimental)]
enum ExternalServiceOutcome: string
{
    case RejectedBeforeAttempt = 'rejected_before_attempt';
    case Succeeded = 'succeeded';
    case FailedBeforeEffect = 'failed_before_effect';
    case CancelledBeforeEffect = 'cancelled_before_effect';
    case CancelledAfterPossibleEffect = 'cancelled_after_possible_effect';
    case UncertainEffect = 'uncertain_effect';
}
