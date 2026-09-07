<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;

/** Binds exact inert provider/model labels to application-declared capabilities. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelProfileReference
{
    public function __construct(
        private ContractVersion $version,
        private ProfileIdentity $identity,
        private ProviderIdentity $providerIdentity,
        private ModelIdentity $modelIdentity,
        private ProviderCapabilitySet $capabilities,
    ) {}

    public function version(): ContractVersion
    {
        return $this->version;
    }

    public function identity(): ProfileIdentity
    {
        return $this->identity;
    }

    public function providerIdentity(): ProviderIdentity
    {
        return $this->providerIdentity;
    }

    public function modelIdentity(): ModelIdentity
    {
        return $this->modelIdentity;
    }

    public function capabilities(): ProviderCapabilitySet
    {
        return $this->capabilities;
    }

    public function equals(self $other): bool
    {
        return $this->identity->equals($other->identity)
            && $this->providerIdentity->equals($other->providerIdentity)
            && $this->modelIdentity->equals($other->modelIdentity)
            && $this->capabilities->equals($other->capabilities);
    }
}
