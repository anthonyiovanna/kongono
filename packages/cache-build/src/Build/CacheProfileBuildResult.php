<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Artifact\CacheProfileArtifact;
use Kongono\CacheBuild\Exception\InvalidCacheProfileArtifactException;
use Kongono\CacheBuild\Profile\CacheProfileSet;

/** Carries one detached profile set and its validated deterministic artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfileBuildResult
{
    public function __construct(
        private CacheProfileSet $profiles,
        private CacheProfileBuildIdentity $buildIdentity,
        private CacheProfileArtifact $artifact,
    ) {
        if (!$profiles->identity()->equals($artifact->sourceIdentity())
            || !$buildIdentity->equals($artifact->buildIdentity())
            || !$profiles->equals($artifact->profiles())) {
            throw InvalidCacheProfileArtifactException::invalid('build_result_relation');
        }
    }

    public function profiles(): CacheProfileSet
    {
        return $this->profiles;
    }

    public function buildIdentity(): CacheProfileBuildIdentity
    {
        return $this->buildIdentity;
    }

    public function artifact(): CacheProfileArtifact
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
