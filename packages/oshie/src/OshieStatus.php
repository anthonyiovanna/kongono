<?php

declare(strict_types=1);

namespace Kongono\Oshie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes every bounded Phase 4F response state without fallback. */
#[Api(ApiStatus::Experimental)]
enum OshieStatus: string
{
    case Available = 'available';
    case Taught = 'taught';
    case Practice = 'practice';
    case Correct = 'correct';
    case Incorrect = 'incorrect';
    case Unsupported = 'unsupported';
    case Unavailable = 'unavailable';
    case Stale = 'stale';
    case Blocked = 'blocked';
}
