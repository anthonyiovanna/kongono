<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects one closed caller-owned cache-control value. */
#[Api(ApiStatus::Experimental)]
enum AssetCachePolicy: string
{
    case NoStore = 'no-store';
    case PrivateRevalidate = 'private, no-cache';
    case PublicImmutable = 'public, max-age=31536000, immutable';
}
