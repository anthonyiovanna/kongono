<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Isolates one bounded native address-information lookup for deterministic tests. */
#[Api(ApiStatus::Internal)]
interface AddressInfoLookup
{
    /** @param array{ai_socktype: int, ai_protocol: int, ai_flags: int} $hints */
    public function lookup(string $host, string $service, array $hints, int $maximumCandidates): AddressInfoLookupResult;
}
