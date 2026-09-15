<?php

declare(strict_types=1);

namespace Kongono\AssetResolution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the closed asset-resolution contract version. */
#[Api(ApiStatus::Experimental)]
enum ContractVersion: int
{
    case Version1 = 1;
}
