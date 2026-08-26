<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the closed Phase 8F read-only framework command family. */
#[Api(ApiStatus::Experimental)]
enum ReadOnlyFrameworkCommand: string
{
    case Composition = 'framework:composition';
    case Graph = 'framework:graph';
    case Quality = 'framework:quality';
}
