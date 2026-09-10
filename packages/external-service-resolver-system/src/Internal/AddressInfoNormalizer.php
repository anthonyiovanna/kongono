<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceResolverSystem\Profile\AddressFamilyPreference;
use Kongono\NetworkEgressRuntime\Address\AddressFamily;
use Kongono\NetworkEgressRuntime\Address\ResolutionEvidence;
use Kongono\NetworkEgressRuntime\Address\ResolvedAddress;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;

/** Validates every detached record before canonical ordering and selection. */
#[Api(ApiStatus::Internal)]
final class AddressInfoNormalizer
{
    /** @param list<AddressInfoRecord> $records */
    public static function normalize(array $records, int $port, AddressFamilyPreference $preference): EgressResolutionResult
    {
        $addresses = [];
        foreach ($records as $record) {
            $values = $record->values();
            $family = $values['ai_family'] ?? null;
            if (!is_int($family) || !in_array($family, [AF_INET, AF_INET6], true)) {
                return SystemResolverEngine::failure(AddressInfoLookupFailure::ResultInvalid);
            }
            if (($values['ai_socktype'] ?? null) !== SOCK_STREAM || ($values['ai_protocol'] ?? null) !== SOL_TCP) {
                return SystemResolverEngine::failure(AddressInfoLookupFailure::ResultInvalid);
            }
            $nativeAddress = $values['ai_addr'] ?? null;
            if (!is_array($nativeAddress)) {
                return SystemResolverEngine::failure(AddressInfoLookupFailure::ResultInvalid);
            }
            $addressKey = AF_INET === $family ? 'sin_addr' : 'sin6_addr';
            $portKey = AF_INET === $family ? 'sin_port' : 'sin6_port';
            $value = $nativeAddress[$addressKey] ?? null;
            if (!is_string($value) || ($nativeAddress[$portKey] ?? null) !== $port) {
                return SystemResolverEngine::failure(AddressInfoLookupFailure::ResultInvalid);
            }
            try {
                $address = new ResolvedAddress($value);
            } catch (InvalidEgressRuntimeException) {
                return SystemResolverEngine::failure(AddressInfoLookupFailure::ResultInvalid);
            }
            $expectedFamily = AF_INET === $family ? AddressFamily::Ipv4 : AddressFamily::Ipv6;
            if ($address->family() !== $expectedFamily) {
                return SystemResolverEngine::failure(AddressInfoLookupFailure::ResultInvalid);
            }
            $addresses[] = $address;
        }

        $ordered = ResolutionOrdering::order($addresses, $preference);
        if ([] === $ordered) {
            return SystemResolverEngine::failure(AddressInfoLookupFailure::NoAddress);
        }

        return new EgressResolutionResult(new ResolutionEvidence($ordered, $ordered[0]), null);
    }

    private function __construct() {}
}
