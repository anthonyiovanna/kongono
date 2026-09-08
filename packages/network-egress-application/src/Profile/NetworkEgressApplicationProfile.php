<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifact;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;

/** Carries one exact source/artifact binding as inert application evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressApplicationProfile
{
    /** @internal Constructed only by the package profile factory. */
    public function __construct(
        private EgressPolicySource $source,
        private EgressPolicyArtifact $artifact,
        private NetworkEgressApplicationProfileIdentity $identity,
    ) {}

    public function source(): EgressPolicySource
    {
        return $this->source;
    }

    public function artifact(): EgressPolicyArtifact
    {
        return $this->artifact;
    }

    public function identity(): NetworkEgressApplicationProfileIdentity
    {
        return $this->identity;
    }
}
