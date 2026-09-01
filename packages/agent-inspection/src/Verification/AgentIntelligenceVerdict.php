<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies independent deterministic comparison evidence. */
#[Api(ApiStatus::Experimental)]
enum AgentIntelligenceVerdict: string
{
    case Pass = 'pass';
    case Fail = 'fail';
}
