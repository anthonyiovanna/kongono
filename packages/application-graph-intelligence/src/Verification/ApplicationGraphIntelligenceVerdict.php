<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Records whether every bounded independent comparison passed. */
#[Api(ApiStatus::Experimental)]
enum ApplicationGraphIntelligenceVerdict: string
{
    case Pass = 'pass';
    case Fail = 'fail';
}
