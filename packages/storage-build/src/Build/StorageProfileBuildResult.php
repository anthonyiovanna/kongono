<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactIdentity;
use Kongono\StorageBuild\Exception\InvalidStorageProfileArtifactException;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageBuild\Profile\StorageProfileSetIdentity;

/** Carries one detached profile set and its validated deterministic artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageProfileBuildResult
{
    public function __construct(
        private StorageProfileSet $profiles,
        private StorageProfileBuildIdentity $buildIdentity,
        private StorageProfileArtifact $artifact,
    ) {
        if (!$profiles->identity()->equals($artifact->sourceIdentity())
            || !$buildIdentity->equals($artifact->buildIdentity())
            || !$profiles->equals($artifact->profiles())) {
            throw InvalidStorageProfileArtifactException::invalid('build_result_relation');
        }
    }

    public function profiles(): StorageProfileSet
    {
        return $this->profiles;
    }

    public function buildIdentity(): StorageProfileBuildIdentity
    {
        return $this->buildIdentity;
    }

    public function sourceIdentity(): StorageProfileSetIdentity
    {
        return $this->profiles->identity();
    }

    public function artifactIdentity(): StorageProfileArtifactIdentity
    {
        return $this->artifact->artifactIdentity();
    }

    public function artifact(): StorageProfileArtifact
    {
        return $this->artifact;
    }

    public function equals(self $other): bool
    {
        return $this->profiles->equals($other->profiles)
            && $this->buildIdentity->equals($other->buildIdentity)
            && $this->artifact->equals($other->artifact);
    }
}
