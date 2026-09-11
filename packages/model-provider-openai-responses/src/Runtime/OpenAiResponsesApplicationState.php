<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the exact single-operation provider application lifecycle. */
#[Api(ApiStatus::Experimental)]
enum OpenAiResponsesApplicationState: string
{
    case Ready = 'ready';
    case Active = 'active';
    case Retired = 'retired';
}
