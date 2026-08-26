<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies one immutable persistence change without carrying field values. */
#[Api(ApiStatus::Experimental)]
enum ChangeKind
{
    case Insert;
    case Update;
    case Delete;
}
