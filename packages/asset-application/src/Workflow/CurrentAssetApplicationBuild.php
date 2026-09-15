<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifact;

/** Carries checked exact-current native metadata without source or output bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentAssetApplicationBuild
{
    public function __construct(
        private AssetResolutionArtifact $resolutionArtifact,
        private string $artifactBasename,
        private int $declarationCount,
        private int $dependencyCount,
        private int $entryPointCount,
        private int $outputCount,
    ) {}

    public function resolutionArtifact(): AssetResolutionArtifact
    {
        return $this->resolutionArtifact;
    }
    public function artifactBasename(): string
    {
        return $this->artifactBasename;
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
}
