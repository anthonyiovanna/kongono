<?php

declare(strict_types=1);

namespace Kongono\Chie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes every bounded Phase 4D response state without fallback. */
#[Api(ApiStatus::Experimental)]
enum ChieStatus: string
{
    case Answered = 'answered';
    case Unsupported = 'unsupported';
    case Unavailable = 'unavailable';
    case Stale = 'stale';
    case Blocked = 'blocked';
}
