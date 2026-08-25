<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares the only accepted version-1 result shapes. */
#[Api(ApiStatus::Experimental)]
enum ResultExpectation
{
    case Rows;
    case Completion;
}
