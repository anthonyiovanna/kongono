<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceResolverSystem\Profile\AddressFamilyPreference;
use Kongono\NetworkEgressRuntime\Address\AddressFamily;
use Kongono\NetworkEgressRuntime\Address\ResolvedAddress;

/** Collapses exact duplicates and orders addresses by family then binary bytes. */
#[Api(ApiStatus::Internal)]
final class ResolutionOrdering
{
    /**
     * @param list<ResolvedAddress> $addresses
     * @return list<ResolvedAddress>
     */
    public static function order(array $addresses, AddressFamilyPreference $preference): array
    {
        $distinct = [];
        foreach ($addresses as $address) {
            $distinct[$address->value()] = $address;
        }
        $preferred = AddressFamilyPreference::Ipv4First === $preference ? AddressFamily::Ipv4 : AddressFamily::Ipv6;
        $ordered = array_values($distinct);
        usort($ordered, static function (ResolvedAddress $left, ResolvedAddress $right) use ($preferred): int {
            if ($left->family() !== $right->family()) {
                return $left->family() === $preferred ? -1 : 1;
            }
            $leftBytes = inet_pton($left->value());
            $rightBytes = inet_pton($right->value());
            if (false === $leftBytes || false === $rightBytes) {
                throw new \LogicException('Previously validated address could not be encoded.');
            }

            return strcmp($leftBytes, $rightBytes);
        });

        return $ordered;
    }

    private function __construct() {}
}
