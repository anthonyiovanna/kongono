<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Outcome;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies mutation evidence while preserving uncertain effects. */
#[Api(ApiStatus::Experimental)]
enum StorageMutationOutcome: string
{
    case Completed = 'completed';
    case Absent = 'absent';
    case AlreadyExists = 'already_exists';
    case Conflict = 'conflict';
    case Rejected = 'rejected';
    case Unsupported = 'unsupported';
    case Unavailable = 'unavailable';
    case Uncertain = 'uncertain';
}
