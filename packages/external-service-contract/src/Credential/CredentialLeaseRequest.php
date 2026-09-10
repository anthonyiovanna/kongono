<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Credential;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;

/** Requests one finite purpose-bound lease without acquiring credential material. */
#[Api(ApiStatus::Experimental)]
final readonly class CredentialLeaseRequest
{
    public function __construct(
        private ExternalServiceOperationIdentity $operationIdentity,
        private CredentialReference $reference,
        private CredentialPurpose $purpose,
        private int $maximumLifetimeMilliseconds,
    ) {
        if ($maximumLifetimeMilliseconds < 1 || $maximumLifetimeMilliseconds > 300000) {
            throw InvalidExternalServiceContractException::invalidCredentialLease('maximum_lifetime_milliseconds_range', 300000);
        }
    }

    public function operationIdentity(): ExternalServiceOperationIdentity
    {
        return $this->operationIdentity;
    }

    public function reference(): CredentialReference
    {
        return $this->reference;
    }

    public function purpose(): CredentialPurpose
    {
        return $this->purpose;
    }

    public function maximumLifetimeMilliseconds(): int
    {
        return $this->maximumLifetimeMilliseconds;
    }

    public function equals(self $other): bool
    {
        return $this->operationIdentity->equals($other->operationIdentity)
            && $this->reference->equals($other->reference)
            && $this->purpose->equals($other->purpose)
            && $this->maximumLifetimeMilliseconds === $other->maximumLifetimeMilliseconds;
    }
}
