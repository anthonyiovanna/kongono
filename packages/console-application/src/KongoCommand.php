<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact closed command family exposed by the supported executable. */
#[Api(ApiStatus::Experimental)]
enum KongoCommand: string
{
    case ApplicationGraphInspect = 'application-graph:inspect';
    case ApplicationBuild = 'application:build';
    case ObservabilityCheck = 'observability:check';
    case ObservabilityInspect = 'observability:inspect';
    case SecurityCheck = 'security:check';
    case SecurityInspect = 'security:inspect';
}
