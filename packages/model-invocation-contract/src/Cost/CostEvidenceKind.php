<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Cost;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Distinguishes provider-reported and estimated cost claims. */
#[Api(ApiStatus::Experimental)]
enum CostEvidenceKind: string
{
    case ProviderReported = 'provider_reported';
    case Estimated = 'estimated';
}
