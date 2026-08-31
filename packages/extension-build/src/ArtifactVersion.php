<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact closed extension declaration artifact schema version. */
#[Api(ApiStatus::Experimental)]
enum ArtifactVersion: int
{
    case Version1 = 1;
}
