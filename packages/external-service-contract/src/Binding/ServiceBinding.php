<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Binding;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Endpoint\ServiceEndpoint;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;

/** Groups immutable secret-free service-binding evidence without granting access. */
#[Api(ApiStatus::Experimental)]
final readonly class ServiceBinding
{
    public function __construct(
        private ServiceBindingIdentity $identity,
        private ServiceProfileIdentity $profile,
        private ServiceEndpoint $endpoint,
        private CredentialReference $credentialReference,
        private TransportCapabilitySet $requiredCapabilities,
    ) {}

    public function identity(): ServiceBindingIdentity
    {
        return $this->identity;
    }

    public function profile(): ServiceProfileIdentity
    {
        return $this->profile;
    }

    public function endpoint(): ServiceEndpoint
    {
        return $this->endpoint;
    }

    public function credentialReference(): CredentialReference
    {
        return $this->credentialReference;
    }

    public function requiredCapabilities(): TransportCapabilitySet
    {
        return $this->requiredCapabilities;
    }

    public function equals(self $other): bool
    {
        return $this->identity->equals($other->identity)
            && $this->profile->equals($other->profile)
            && $this->endpoint->equals($other->endpoint)
            && $this->credentialReference->equals($other->credentialReference)
            && $this->requiredCapabilities->all() === $other->requiredCapabilities->all();
    }
}
