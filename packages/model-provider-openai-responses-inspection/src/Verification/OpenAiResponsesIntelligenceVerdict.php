<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies deterministic independent comparison evidence. */
#[Api(ApiStatus::Experimental)]
enum OpenAiResponsesIntelligenceVerdict: string
{
    case Pass = 'pass';
    case Fail = 'fail';
}
