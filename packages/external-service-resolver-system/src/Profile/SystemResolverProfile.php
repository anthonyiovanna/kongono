<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceResolverSystem\Exception\InvalidSystemResolverProfileException;

/** Carries the explicit family preference and finite native candidate bound. */
#[Api(ApiStatus::Experimental)]
final readonly class SystemResolverProfile
{
    public function __construct(
        private AddressFamilyPreference $addressFamilyPreference,
        private int $maximumCandidates,
    ) {
        if ($maximumCandidates < 1 || $maximumCandidates > 64) {
            throw InvalidSystemResolverProfileException::candidateLimit($maximumCandidates);
        }
    }

    public function addressFamilyPreference(): AddressFamilyPreference
    {
        return $this->addressFamilyPreference;
    }

    public function maximumCandidates(): int
    {
        return $this->maximumCandidates;
    }

    public function equals(self $other): bool
    {
        return $this->addressFamilyPreference === $other->addressFamilyPreference
            && $this->maximumCandidates === $other->maximumCandidates;
    }
}
