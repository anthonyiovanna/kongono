<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Stream;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names one normalized partial-evidence event shape. */
#[Api(ApiStatus::Experimental)]
enum StreamEventKind: string
{
    case Started = 'started';
    case ContentDelta = 'content_delta';
    case ActionName = 'action_name';
    case ActionArgumentsDelta = 'action_arguments_delta';
    case Usage = 'usage';
    case Completed = 'completed';
    case Failed = 'failed';
}
