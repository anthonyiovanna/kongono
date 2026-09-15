<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Build\AssetBuildIdentity;
use Kongono\AssetBuild\Build\NativeBuildProfile;
use Kongono\AssetBuild\Exception\InvalidAssetBuildArtifactException;
use Kongono\AssetBuild\Graph\AssetGraphIdentity;
use Kongono\AssetBuild\Source\AssetSourceSetIdentity;

/** Carries one completely structurally validated canonical native-build artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetBuildArtifact
{
    public function __construct(
        private string $bytes,
        private AssetBuildIdentity $buildIdentity,
        private AssetSourceSetIdentity $sourceSetIdentity,
        private AssetGraphIdentity $graphIdentity,
        private NativeBuildProfile $profile,
        private int $declarationCount,
        private int $dependencyCount,
        private int $entryPointCount,
        private int $outputCount,
    ) {
        if (!$buildIdentity->equals(AssetBuildIdentity::fromArtifactBytes($bytes)) || min($declarationCount, $dependencyCount, $entryPointCount, $outputCount) < 0 || $declarationCount > 1024 || $dependencyCount > 4096 || $entryPointCount > 256 || $outputCount !== $declarationCount) {
            throw InvalidAssetBuildArtifactException::invalid('artifact_identity_relation');
        }
    }

    public function bytes(): string
    {
        return $this->bytes;
    }
    public function buildIdentity(): AssetBuildIdentity
    {
        return $this->buildIdentity;
    }
    public function sourceSetIdentity(): AssetSourceSetIdentity
    {
        return $this->sourceSetIdentity;
    }
    public function graphIdentity(): AssetGraphIdentity
    {
        return $this->graphIdentity;
    }
    public function profile(): NativeBuildProfile
    {
        return $this->profile;
    }
    public function declarationCount(): int
    {
        return $this->declarationCount;
    }
    public function dependencyCount(): int
    {
        return $this->dependencyCount;
    }
    public function entryPointCount(): int
    {
        return $this->entryPointCount;
    }
    public function outputCount(): int
    {
        return $this->outputCount;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->bytes, $other->bytes) && $this->buildIdentity->equals($other->buildIdentity) && $this->sourceSetIdentity->equals($other->sourceSetIdentity) && $this->graphIdentity->equals($other->graphIdentity);
    }
}
