<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Describes the observable lifecycle state of one persistence context. */
#[Api(ApiStatus::Experimental)]
enum ContextState
{
    case Active;
    case Flushing;
    case Failed;
    case Retired;
}
