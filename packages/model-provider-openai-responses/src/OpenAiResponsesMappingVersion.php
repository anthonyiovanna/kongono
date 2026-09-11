<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies Kongono's exact offline OpenAI Responses mapping vocabulary. */
#[Api(ApiStatus::Experimental)]
enum OpenAiResponsesMappingVersion: string
{
    case Version1 = 'kongono-openai-responses/1';
    case Version2 = 'kongono-openai-responses/2';
}
