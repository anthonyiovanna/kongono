<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Invocation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects the normalized result shape requested from a future adapter. */
#[Api(ApiStatus::Experimental)]
enum ResultMode: string
{
    case Text = 'text';
    case Structured = 'structured';
    case ActionSuggestion = 'action_suggestion';
}
