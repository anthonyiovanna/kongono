<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Normalizes a claimed terminal reason without proving provider behavior. */
#[Api(ApiStatus::Experimental)]
enum FinishReason: string
{
    case Completed = 'completed';
    case Length = 'length';
    case ContentFiltered = 'content_filtered';
    case ActionSuggested = 'action_suggested';
    case Cancelled = 'cancelled';
    case Unknown = 'unknown';
}
