<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Stream;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the closed provider event vocabulary accepted by mapping version 1. */
#[Api(ApiStatus::Internal)]
enum ResponsesSseEventKind: string
{
    case ResponseCreated = 'response.created';
    case ResponseInProgress = 'response.in_progress';
    case OutputItemAdded = 'response.output_item.added';
    case ContentPartAdded = 'response.content_part.added';
    case OutputTextDelta = 'response.output_text.delta';
    case OutputTextDone = 'response.output_text.done';
    case ContentPartDone = 'response.content_part.done';
    case OutputItemDone = 'response.output_item.done';
    case ResponseCompleted = 'response.completed';
    case ResponseIncomplete = 'response.incomplete';
    case ResponseFailed = 'response.failed';
}
