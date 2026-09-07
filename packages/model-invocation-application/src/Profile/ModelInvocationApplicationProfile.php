<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifact;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;

/** Carries one exact source/artifact binding as inert application evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationApplicationProfile
{
    /** @internal Constructed only by the package profile factory. */
    public function __construct(
        private InvocationPlanSource $source,
        private InvocationPlanArtifact $artifact,
        private ModelInvocationApplicationProfileIdentity $identity,
    ) {}

    public function source(): InvocationPlanSource
    {
        return $this->source;
    }

    public function artifact(): InvocationPlanArtifact
    {
        return $this->artifact;
    }

    public function identity(): ModelInvocationApplicationProfileIdentity
    {
        return $this->identity;
    }
}
