<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Response;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the closed incomplete reasons supported by mapping version 1. */
#[Api(ApiStatus::Internal)]
enum ResponseIncompleteReason: string
{
    case MaxTokens = 'max_tokens';
    case ContentFilter = 'content_filter';
}
