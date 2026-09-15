<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Dependency;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies one inert dependency relation kind. */
#[Api(ApiStatus::Experimental)]
enum AssetDependencyKind: string
{
    case Static = 'static';
    case Dynamic = 'dynamic';
    case Resource = 'resource';
}
