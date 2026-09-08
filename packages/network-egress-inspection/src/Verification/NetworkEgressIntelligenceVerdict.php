<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies deterministic independent comparison evidence. */
#[Api(ApiStatus::Experimental)]
enum NetworkEgressIntelligenceVerdict: string
{
    case Pass = 'pass';
    case Fail = 'fail';
}
