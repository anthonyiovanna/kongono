<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the closed pull-based streaming session lifecycle. */
#[Api(ApiStatus::Internal)]
enum OpenAiResponsesStreamingInvocationSessionState: string
{
    case Ready = 'ready';
    case Active = 'active';
    case Streaming = 'streaming';
    case Terminal = 'terminal';
    case Exhausted = 'exhausted';
    case Failed = 'failed';
    case Closed = 'closed';
}
