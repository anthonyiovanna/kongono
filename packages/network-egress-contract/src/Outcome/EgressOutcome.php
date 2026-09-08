<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Outcome;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names terminal operation evidence without authorizing further effects. */
#[Api(ApiStatus::Experimental)]
enum EgressOutcome: string
{
    case RejectedBeforeAttempt = 'rejected_before_attempt';
    case Succeeded = 'succeeded';
    case FailedBeforeEffect = 'failed_before_effect';
    case CancelledBeforeEffect = 'cancelled_before_effect';
    case CancelledAfterPossibleEffect = 'cancelled_after_possible_effect';
    case UncertainEffect = 'uncertain_effect';
}
