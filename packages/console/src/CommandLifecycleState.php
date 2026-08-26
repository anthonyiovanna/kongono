<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies whether one command operation remains usable or has been retired. */
#[Api(ApiStatus::Experimental)]
enum CommandLifecycleState: string
{
    case Active = 'active';
    case Retired = 'retired';
}
