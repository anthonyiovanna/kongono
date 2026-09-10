<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Internal;

use AddressInfo;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Performs and detaches exactly one bounded operating-system getaddrinfo result. */
#[Api(ApiStatus::Internal)]
final class NativeAddressInfoLookup implements AddressInfoLookup
{
    public function lookup(string $host, string $service, array $hints, int $maximumCandidates): AddressInfoLookupResult
    {
        $entries = @socket_addrinfo_lookup($host, $service, $hints);
        if (false === $entries) {
            return AddressInfoLookupResult::failed(AddressInfoLookupFailure::LookupFailed);
        }
        if ([] === $entries) {
            return AddressInfoLookupResult::failed(AddressInfoLookupFailure::NoAddress);
        }
        if (count($entries) > $maximumCandidates) {
            return AddressInfoLookupResult::failed(AddressInfoLookupFailure::CandidateLimit);
        }

        $records = [];
        foreach ($entries as $entry) {
            if (!self::isAddressInfo($entry)) {
                return AddressInfoLookupResult::failed(AddressInfoLookupFailure::ResultInvalid);
            }
            $records[] = new AddressInfoRecord(socket_addrinfo_explain($entry));
        }

        return AddressInfoLookupResult::succeeded($records);
    }

    private static function isAddressInfo(mixed $entry): bool
    {
        return $entry instanceof AddressInfo;
    }
}
