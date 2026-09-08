<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Cancellation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names retrospective cancellation evidence without acting as a token. */
#[Api(ApiStatus::Experimental)]
enum CancellationState: string
{
    case NotRequested = 'not_requested';
    case RequestedBeforeAttempt = 'requested_before_attempt';
    case RequestedAfterPossibleEffect = 'requested_after_possible_effect';
}
