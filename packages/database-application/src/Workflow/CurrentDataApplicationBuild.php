<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Workflow\CurrentApplicationBuild;
use Kongono\DatabaseMapping\Runtime\LoadedGeneratedDataAccess;

/** Carries the four checked build identities and detached exact-current load metadata. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentDataApplicationBuild
{
    public function __construct(
        private string $mappingArtifactPath,
        private string $mappingArtifactBytes,
        private string $mappingArtifactSha256,
        private string $generatedDataAccessPath,
        private string $generatedDataAccessSha256,
        private LoadedGeneratedDataAccess $loadedDataAccess,
        private CurrentApplicationBuild $applicationBuild,
    ) {}

    public function mappingArtifactPath(): string
    {
        return $this->mappingArtifactPath;
    }

    public function mappingArtifactBytes(): string
    {
        return $this->mappingArtifactBytes;
    }

    public function mappingArtifactSha256(): string
    {
        return $this->mappingArtifactSha256;
    }

    public function generatedDataAccessPath(): string
    {
        return $this->generatedDataAccessPath;
    }

    public function generatedDataAccessSha256(): string
    {
        return $this->generatedDataAccessSha256;
    }

    public function loadedDataAccess(): LoadedGeneratedDataAccess
    {
        return $this->loadedDataAccess;
    }

    public function applicationBuild(): CurrentApplicationBuild
    {
        return $this->applicationBuild;
    }
}
