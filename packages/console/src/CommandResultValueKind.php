<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact scalar kind retained by one structured result value. */
#[Api(ApiStatus::Experimental)]
enum CommandResultValueKind: string
{
    case Text = 'text';
    case Integer = 'integer';
    case Boolean = 'boolean';
}
