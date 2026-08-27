<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes only the exact identity of caller-owned opaque policy evidence. */
#[Api(ApiStatus::Experimental)]
interface AuthorizationPolicyEvidence
{
    public function reference(): AuthorizationPolicyReference;
}
