<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifact;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;

/** Carries exact-current secret-free binding and egress evidence without authority. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceApplicationProfile
{
    /** @internal Constructed only by the package profile factory. */
    public function __construct(
        private ExternalServiceBindingSource $bindingSource,
        private ExternalServiceBindingArtifact $bindingArtifact,
        private ServiceBinding $binding,
        private NetworkEgressApplicationProfile $networkEgressProfile,
        private ExternalServiceApplicationProfileIdentity $identity,
    ) {}

    public function bindingSource(): ExternalServiceBindingSource
    {
        return $this->bindingSource;
    }

    public function bindingArtifact(): ExternalServiceBindingArtifact
    {
        return $this->bindingArtifact;
    }

    public function binding(): ServiceBinding
    {
        return $this->binding;
    }

    public function networkEgressProfile(): NetworkEgressApplicationProfile
    {
        return $this->networkEgressProfile;
    }

    public function identity(): ExternalServiceApplicationProfileIdentity
    {
        return $this->identity;
    }
}
