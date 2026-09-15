<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects the closed adapter-contract vocabulary. */
#[Api(ApiStatus::Experimental)]
enum AdapterContractVersion: int
{
    case Version1 = 1;
}
