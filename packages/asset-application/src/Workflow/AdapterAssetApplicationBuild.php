<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifact;

/** Carries completely revalidated adapter metadata without retaining output bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterAssetApplicationBuild
{
    public function __construct(
        private AssetResolutionArtifact $resolutionArtifact,
        private string $adapterProfile,
        private int $entryCount,
        private int $dependencyCount,
        private int $outputCount,
    ) {}

    public function resolutionArtifact(): AssetResolutionArtifact
    {
        return $this->resolutionArtifact;
    }
    public function adapterProfile(): string
    {
        return $this->adapterProfile;
    }
    public function entryCount(): int
    {
        return $this->entryCount;
    }
    public function dependencyCount(): int
    {
        return $this->dependencyCount;
    }
    public function outputCount(): int
    {
        return $this->outputCount;
    }
}
