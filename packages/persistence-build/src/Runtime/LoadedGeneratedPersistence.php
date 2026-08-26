<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries detached exact identities and class maps for one ordered generated load. */
#[Api(ApiStatus::Experimental)]
final readonly class LoadedGeneratedPersistence
{
    /**
     * @param array<string, string> $entityStateClasses
     * @param array<string, string> $aggregateRepositoryClasses
     */
    public function __construct(
        private string $persistenceArtifactSha256,
        private string $generatedStatePath,
        private string $generatedStateSha256,
        private string $generatedStateNamespace,
        private array $entityStateClasses,
        private string $generatedRepositoryPath,
        private string $generatedRepositorySha256,
        private string $generatedRepositoryNamespace,
        private array $aggregateRepositoryClasses,
    ) {}

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

    public function generatedStateNamespace(): string
    {
        return $this->generatedStateNamespace;
    }

    /** @return array<string, string> */
    public function entityStateClasses(): array
    {
        return [...$this->entityStateClasses];
    }

    public function generatedRepositoryPath(): string
    {
        return $this->generatedRepositoryPath;
    }

    public function generatedRepositorySha256(): string
    {
        return $this->generatedRepositorySha256;
    }

    public function generatedRepositoryNamespace(): string
    {
        return $this->generatedRepositoryNamespace;
    }

    /** @return array<string, string> */
    public function aggregateRepositoryClasses(): array
    {
        return [...$this->aggregateRepositoryClasses];
    }
}
