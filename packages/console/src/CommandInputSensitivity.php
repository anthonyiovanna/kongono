<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies input sensitivity as evidence for later explicit policy. */
#[Api(ApiStatus::Experimental)]
enum CommandInputSensitivity: string
{
    case Ordinary = 'ordinary';
    case Sensitive = 'sensitive';
}
