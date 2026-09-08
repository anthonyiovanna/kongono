<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Lifecycle;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names lifecycle evidence without implementing transitions. */
#[Api(ApiStatus::Experimental)]
enum EgressLifecycleState: string
{
    case Created = 'created';
    case Validated = 'validated';
    case Authorized = 'authorized';
    case ResolutionPending = 'resolution_pending';
    case Resolved = 'resolved';
    case AttemptPending = 'attempt_pending';
    case RequestStreaming = 'request_streaming';
    case ResponseStreaming = 'response_streaming';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Uncertain = 'uncertain';
    case Retired = 'retired';
}
