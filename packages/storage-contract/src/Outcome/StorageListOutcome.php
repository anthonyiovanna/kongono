<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Outcome;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies one bounded list result without discovery authority. */
#[Api(ApiStatus::Experimental)]
enum StorageListOutcome: string
{
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Unsupported = 'unsupported';
    case Unavailable = 'unavailable';
}
