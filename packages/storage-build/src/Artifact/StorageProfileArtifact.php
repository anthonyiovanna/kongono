<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Build\StorageProfileBuildIdentity;
use Kongono\StorageBuild\Exception\InvalidStorageProfileArtifactException;
use Kongono\StorageBuild\Internal\CanonicalJson;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageBuild\Profile\StorageProfileSetIdentity;

/** Carries one completely structurally validated canonical profile artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageProfileArtifact
{
    public function __construct(
        private string $bytes,
        private StorageProfileSetIdentity $sourceIdentity,
        private StorageProfileBuildIdentity $buildIdentity,
        private StorageProfileArtifactIdentity $artifactIdentity,
        private StorageProfileSet $profiles,
    ) {
        if ('' === $bytes || strlen($bytes) > CanonicalJson::MAX_BYTES
            || !$sourceIdentity->equals($profiles->identity())
            || !$buildIdentity->equals(StorageProfileBuildIdentity::fromSourceIdentity($sourceIdentity))
            || !$artifactIdentity->equals(StorageProfileArtifactIdentity::fromBytes($bytes))) {
            throw InvalidStorageProfileArtifactException::invalid('artifact_identity_relation');
        }
    }

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function byteCount(): int
    {
        return strlen($this->bytes);
    }

    public function sourceIdentity(): StorageProfileSetIdentity
    {
        return $this->sourceIdentity;
    }

    public function buildIdentity(): StorageProfileBuildIdentity
    {
        return $this->buildIdentity;
    }

    public function artifactIdentity(): StorageProfileArtifactIdentity
    {
        return $this->artifactIdentity;
    }

    public function profileCount(): int
    {
        return $this->profiles->profileCount();
    }

    public function profiles(): StorageProfileSet
    {
        return $this->profiles;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->bytes, $other->bytes)
            && $this->sourceIdentity->equals($other->sourceIdentity)
            && $this->buildIdentity->equals($other->buildIdentity)
            && $this->artifactIdentity->equals($other->artifactIdentity)
            && $this->profiles->equals($other->profiles);
    }
}
