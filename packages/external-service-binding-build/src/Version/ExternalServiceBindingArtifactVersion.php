<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Version;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the canonical external-service binding artifact format. */
#[Api(ApiStatus::Experimental)]
enum ExternalServiceBindingArtifactVersion: int
{
    case V1 = 1;
}
