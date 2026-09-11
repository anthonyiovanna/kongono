<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the closed one-shot provider-session lifecycle. */
#[Api(ApiStatus::Internal)]
enum OpenAiResponsesInvocationSessionState: string
{
    case Ready = 'ready';
    case Active = 'active';
    case Emitted = 'emitted';
    case Exhausted = 'exhausted';
    case Failed = 'failed';
    case Closed = 'closed';
}
