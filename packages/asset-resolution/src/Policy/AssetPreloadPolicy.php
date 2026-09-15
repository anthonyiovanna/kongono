<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects whether validated inert preload hints are retained. */
#[Api(ApiStatus::Experimental)]
enum AssetPreloadPolicy: string
{
    case Omit = 'omit';
    case HonorArtifact = 'honor_artifact';
}
