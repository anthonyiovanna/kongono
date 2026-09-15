<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the single fixed native byte-copy build profile. */
#[Api(ApiStatus::Experimental)]
enum NativeBuildProfile: string
{
    case NativeCopyV1 = 'native-copy/1';
}
