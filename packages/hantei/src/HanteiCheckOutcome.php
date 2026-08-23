<?php

declare(strict_types=1);

namespace Kongono\Hantei;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the closed outcome of one selected framework-quality check. */
#[Api(ApiStatus::Experimental)]
enum HanteiCheckOutcome: string
{
    case Pass = 'PASS';
    case Fail = 'FAIL';
    case Blocked = 'BLOCKED';
}
