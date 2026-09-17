<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Build\CacheProfileBuildIdentity;
use Kongono\CacheBuild\Exception\InvalidCacheProfileArtifactException;
use Kongono\CacheBuild\Internal\CanonicalJson;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileSetIdentity;

/** Carries one completely structurally validated canonical profile artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfileArtifact
{
    public function __construct(
        private string $bytes,
        private CacheProfileSetIdentity $sourceIdentity,
        private CacheProfileBuildIdentity $buildIdentity,
        private CacheProfileArtifactIdentity $artifactIdentity,
        private CacheProfileSet $profiles,
    ) {
        if ('' === $bytes || strlen($bytes) > CanonicalJson::MAX_BYTES
            || !$sourceIdentity->equals($profiles->identity())
            || !$buildIdentity->equals(CacheProfileBuildIdentity::fromSourceIdentity($sourceIdentity))
            || !$artifactIdentity->equals(CacheProfileArtifactIdentity::fromBytes($bytes))) {
            throw InvalidCacheProfileArtifactException::invalid('artifact_identity_relation');
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

    public function sourceIdentity(): CacheProfileSetIdentity
    {
        return $this->sourceIdentity;
    }

    public function buildIdentity(): CacheProfileBuildIdentity
    {
        return $this->buildIdentity;
    }

    public function artifactIdentity(): CacheProfileArtifactIdentity
    {
        return $this->artifactIdentity;
    }

    public function profileCount(): int
    {
        return $this->profiles->profileCount();
    }

    public function profiles(): CacheProfileSet
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
