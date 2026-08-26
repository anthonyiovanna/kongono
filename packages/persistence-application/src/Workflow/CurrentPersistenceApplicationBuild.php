<?php

declare(strict_types=1);

namespace Kongono\PersistenceApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Workflow\CurrentApplicationBuild;
use Kongono\DatabaseApplication\Workflow\CurrentDataAccessBuild;
use Kongono\PersistenceBuild\Runtime\LoadedGeneratedPersistence;

/** Carries the exact checked identities for one current persistence application. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentPersistenceApplicationBuild
{
    public function __construct(
        private CurrentDataAccessBuild $dataAccessBuild,
        private string $persistenceArtifactPath,
        private string $persistenceArtifactBytes,
        private string $persistenceArtifactSha256,
        private string $generatedStatePath,
        private string $generatedStateSha256,
        private string $generatedRepositoryPath,
        private string $generatedRepositorySha256,
        private LoadedGeneratedPersistence $loadedPersistence,
        private CurrentApplicationBuild $applicationBuild,
    ) {}

    public function dataAccessBuild(): CurrentDataAccessBuild
    {
        return $this->dataAccessBuild;
    }

    public function persistenceArtifactPath(): string
    {
        return $this->persistenceArtifactPath;
    }

    public function persistenceArtifactBytes(): string
    {
        return $this->persistenceArtifactBytes;
    }

    public function persistenceArtifactSha256(): string
    {
        return $this->persistenceArtifactSha256;
    }

    public function generatedStatePath(): string
    {
        return $this->generatedStatePath;
    }

    public function generatedStateSha256(): string
    {
        return $this->generatedStateSha256;
    }

    public function generatedRepositoryPath(): string
    {
        return $this->generatedRepositoryPath;
    }

    public function generatedRepositorySha256(): string
    {
        return $this->generatedRepositorySha256;
    }

    public function loadedPersistence(): LoadedGeneratedPersistence
    {
        return $this->loadedPersistence;
    }

    public function applicationBuild(): CurrentApplicationBuild
    {
        return $this->applicationBuild;
    }
}
