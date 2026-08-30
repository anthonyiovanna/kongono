<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact closed Application Graph contract vocabulary version. */
#[Api(ApiStatus::Experimental)]
enum ContractVersion: int
{
    case Version1 = 1;
}
