<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Artifact\CacheProfileArtifact;
use Kongono\CacheBuild\Artifact\CacheProfileArtifactIdentity;
use Kongono\CacheBuild\Internal\BuildGuard;
use Kongono\CacheBuild\Internal\CanonicalJson;
use Kongono\CacheBuild\Profile\CacheProfileSet;

/** Builds the fixed Version 1 canonical cache-profile artifact without I/O. */
#[Api(ApiStatus::Experimental)]
final class CacheProfileBuilder
{
    public function build(CacheProfileSet $profiles): CacheProfileBuildResult
    {
        $buildIdentity = CacheProfileBuildIdentity::fromSourceIdentity($profiles->identity());
        $bytes = CanonicalJson::encode([
            'schema' => BuildGuard::BUILD_PROFILE,
            'source_identity' => $profiles->identity()->value(),
            'build_identity' => $buildIdentity->value(),
            'profile_count' => $profiles->profileCount(),
            'profiles' => BuildGuard::profileRows($profiles->profiles()),
        ]);
        $artifact = new CacheProfileArtifact(
            $bytes,
            $profiles->identity(),
            $buildIdentity,
            CacheProfileArtifactIdentity::fromBytes($bytes),
            $profiles,
        );

        return new CacheProfileBuildResult($profiles, $buildIdentity, $artifact);
    }
}
