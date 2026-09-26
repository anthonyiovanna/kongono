<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\AdapterContractVersion;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuaranteeSet;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterLimitationSet;
use Kongono\StorageAdapterContract\Internal\AdapterContractGuard;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactIdentity;
use Kongono\StorageBuild\Build\StorageProfileBuildIdentity;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSetIdentity;

/** Binds adapter claims to one exact validated artifact profile. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageAdapterDescriptor
{
    private StorageProfileDeclaration $profile;

    public function __construct(
        private AdapterContractVersion $contractVersion,
        private StorageAdapterIdentity $identity,
        private StorageProfileArtifact $artifact,
        private StorageProfileName $profileName,
        private StorageAdapterGuaranteeSet $guarantees,
        private StorageAdapterLimitationSet $limitations,
    ) {
        $this->profile = AdapterContractGuard::profile($artifact, $profileName);
    }

    public function contractVersion(): AdapterContractVersion
    {
        return $this->contractVersion;
    }

    public function identity(): StorageAdapterIdentity
    {
        return $this->identity;
    }

    public function artifact(): StorageProfileArtifact
    {
        return $this->artifact;
    }

    public function artifactIdentity(): StorageProfileArtifactIdentity
    {
        return $this->artifact->artifactIdentity();
    }

    public function sourceIdentity(): StorageProfileSetIdentity
    {
        return $this->artifact->sourceIdentity();
    }

    public function buildIdentity(): StorageProfileBuildIdentity
    {
        return $this->artifact->buildIdentity();
    }

    public function profileName(): StorageProfileName
    {
        return $this->profileName;
    }

    public function profile(): StorageProfileDeclaration
    {
        return $this->profile;
    }

    public function guarantees(): StorageAdapterGuaranteeSet
    {
        return $this->guarantees;
    }

    public function limitations(): StorageAdapterLimitationSet
    {
        return $this->limitations;
    }
}
