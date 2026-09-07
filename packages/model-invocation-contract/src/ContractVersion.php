<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact neutral contract vocabulary used by a value. */
#[Api(ApiStatus::Experimental)]
enum ContractVersion: int
{
    case Version1 = 1;
}
