<?php

declare(strict_types=1);

namespace Kongono\Hantei;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the conclusive or blocked result of one exact Hantei run. */
#[Api(ApiStatus::Experimental)]
enum HanteiVerdict: string
{
    case Pass = 'PASS';
    case Fail = 'FAIL';
    case Blocked = 'BLOCKED';
}
