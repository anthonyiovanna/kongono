<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Artifact\CacheProfileArtifact;
use Kongono\CacheBuild\Profile\CacheProfileSet;

/** Carries checked exact-current cache-profile evidence for explicit composition. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentCacheApplicationBuild
{
    public function __construct(private CacheProfileSet $profiles, private CacheProfileArtifact $artifact) {}
    public function profiles(): CacheProfileSet
    {
        return $this->profiles;
    }
    public function artifact(): CacheProfileArtifact
    {
        return $this->artifact;
    }
}
