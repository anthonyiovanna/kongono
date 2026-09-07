<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies deterministic independent comparison evidence. */
#[Api(ApiStatus::Experimental)]
enum ModelInvocationIntelligenceVerdict: string
{
    case Pass = 'pass';
    case Fail = 'fail';
}
