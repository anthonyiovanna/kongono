<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact versioned representation carried by a presentation. */
#[Api(ApiStatus::Experimental)]
enum CommandMediaType: string
{
    case HumanText = 'text/plain;charset=utf-8';
    case MachineResultJson = 'application/vnd.kongono.command-result+json;version=1';
    case MachineHelpJson = 'application/vnd.kongono.command-help+json;version=1';
}
