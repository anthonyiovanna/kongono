<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Artifact\StorageProfileArtifactIdentity;
use Kongono\StorageBuild\Internal\BuildGuard;
use Kongono\StorageBuild\Internal\CanonicalJson;
use Kongono\StorageBuild\Profile\StorageProfileSet;

/** Builds the fixed Version 1 canonical storage-profile artifact without I/O. */
#[Api(ApiStatus::Experimental)]
final class StorageProfileBuilder
{
    public function build(StorageProfileSet $profiles): StorageProfileBuildResult
    {
        $buildIdentity = StorageProfileBuildIdentity::fromSourceIdentity($profiles->identity());
        $bytes = CanonicalJson::encode([
            'schema' => BuildGuard::BUILD_PROFILE,
            'source_identity' => $profiles->identity()->value(),
            'build_identity' => $buildIdentity->value(),
            'profile_count' => $profiles->profileCount(),
            'profiles' => BuildGuard::profileRows($profiles->profiles()),
        ]);
        $artifact = new StorageProfileArtifact(
            $bytes,
            $profiles->identity(),
            $buildIdentity,
            StorageProfileArtifactIdentity::fromBytes($bytes),
            $profiles,
        );

        return new StorageProfileBuildResult($profiles, $buildIdentity, $artifact);
    }
}
