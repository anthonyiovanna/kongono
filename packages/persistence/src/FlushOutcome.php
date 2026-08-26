<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies the bounded neutral outcome of a persistence flush attempt. */
#[Api(ApiStatus::Experimental)]
enum FlushOutcome
{
    case NoChanges;
    case Applied;
    case EntityAbsent;
    case OptimisticConflict;
}
