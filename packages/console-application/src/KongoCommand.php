<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the sole command exposed by the supported Phase 8G executable. */
#[Api(ApiStatus::Experimental)]
enum KongoCommand: string
{
    case ApplicationBuild = 'application:build';
}
