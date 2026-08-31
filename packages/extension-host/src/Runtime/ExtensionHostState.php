<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies whether one trusted extension host accepts work, is retired, or is closed. */
#[Api(ApiStatus::Experimental)]
enum ExtensionHostState: string
{
    case Active = 'active';
    case Retired = 'retired';
    case Shutdown = 'shutdown';
}
