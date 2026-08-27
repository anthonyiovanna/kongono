<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact closed command family exposed by the supported executable. */
#[Api(ApiStatus::Experimental)]
enum KongoCommand: string
{
    case ApplicationBuild = 'application:build';
    case SecurityCheck = 'security:check';
    case SecurityInspect = 'security:inspect';
}
