<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Provider;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares one inert capability of an application-owned model profile. */
#[Api(ApiStatus::Experimental)]
enum ProviderCapability: string
{
    case TextResult = 'text_result';
    case StructuredResult = 'structured_result';
    case ActionSuggestion = 'action_suggestion';
    case Streaming = 'streaming';
    case UsageEvidence = 'usage_evidence';
    case CostEvidence = 'cost_evidence';
}
