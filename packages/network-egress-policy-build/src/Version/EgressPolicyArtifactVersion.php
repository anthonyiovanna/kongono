<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Version;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact canonical network-egress policy artifact shape. */
#[Api(ApiStatus::Experimental)]
enum EgressPolicyArtifactVersion: int
{
    case V1 = 1;
}
