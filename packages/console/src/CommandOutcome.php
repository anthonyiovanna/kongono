<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines the exact presentation-neutral outcome of one command invocation. */
#[Api(ApiStatus::Experimental)]
enum CommandOutcome: string
{
    case Succeeded = 'succeeded';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
