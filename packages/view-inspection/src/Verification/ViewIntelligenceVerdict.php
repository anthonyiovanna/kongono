<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies deterministic independent comparison evidence. */
#[Api(ApiStatus::Experimental)]
enum ViewIntelligenceVerdict: string
{
    case Pass = 'pass';
    case Fail = 'fail';
}
