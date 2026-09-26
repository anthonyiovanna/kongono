<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Outcome;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies a read result without granting retry or provider authority. */
#[Api(ApiStatus::Experimental)]
enum StorageReadOutcome: string
{
    case Completed = 'completed';
    case Absent = 'absent';
    case Rejected = 'rejected';
    case Unsupported = 'unsupported';
    case Unavailable = 'unavailable';
}
