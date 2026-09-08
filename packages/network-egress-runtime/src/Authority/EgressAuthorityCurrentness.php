<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Authority;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;

/** Probes caller-owned authority evidence without evaluating authorization. */
#[Api(ApiStatus::Experimental)]
interface EgressAuthorityCurrentness
{
    public function isCurrent(EgressAuthorityReference $reference, CanonicalOrigin $origin): bool;
}
