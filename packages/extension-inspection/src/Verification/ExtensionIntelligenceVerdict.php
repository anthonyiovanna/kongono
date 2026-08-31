<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Records whether every bounded independent comparison passed. */
#[Api(ApiStatus::Experimental)]
enum ExtensionIntelligenceVerdict: string
{
    case Pass = 'pass';
    case Fail = 'fail';
}
