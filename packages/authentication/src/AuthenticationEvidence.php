<?php

declare(strict_types=1);

namespace Kongono\Authentication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Marks already-detached adapter-owned evidence without exposing its payload. */
#[Api(ApiStatus::Experimental)]
interface AuthenticationEvidence
{
    public function mechanism(): AuthenticationMechanism;
}
