<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies deterministic independent comparison evidence. */
#[Api(ApiStatus::Experimental)]
enum CacheIntelligenceVerdict: string
{
    case Pass = 'pass';
    case Fail = 'fail';
}
