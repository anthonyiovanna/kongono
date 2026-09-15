<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies deterministic independent comparison evidence. */
#[Api(ApiStatus::Experimental)]
enum AssetIntelligenceVerdict: string
{
    case Pass = 'pass';
    case Fail = 'fail';
}
