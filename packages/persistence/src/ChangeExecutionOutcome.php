<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies one known database outcome without representing uncertain failure. */
#[Api(ApiStatus::Experimental)]
enum ChangeExecutionOutcome
{
    case Applied;
    case EntityAbsent;
    case OptimisticConflict;
}
