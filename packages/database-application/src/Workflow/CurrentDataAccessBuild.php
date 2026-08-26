<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Runtime\LoadedGeneratedDataAccess;

/** Carries detached exact-current mapping and generated data-access evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentDataAccessBuild
{
    /**
     * @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $mappingSnapshot
     * @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $generatedSnapshot
     */
    public function __construct(
        private string $applicationRoot,
        private string $mappingArtifactSha256,
        private string $generatedDataAccessSha256,
        private LoadedGeneratedDataAccess $loadedDataAccess,
        private array $mappingSnapshot,
        private array $generatedSnapshot,
    ) {}

    public function applicationRoot(): string
    {
        return $this->applicationRoot;
    }

    public function mappingArtifactPath(): string
    {
        return $this->mappingSnapshot['absolute'];
    }

    public function mappingArtifactBytes(): string
    {
        return $this->mappingSnapshot['bytes'];
    }

    public function mappingArtifactSha256(): string
    {
        return $this->mappingArtifactSha256;
    }

    public function generatedDataAccessPath(): string
    {
        return $this->generatedSnapshot['absolute'];
    }

    public function generatedDataAccessSha256(): string
    {
        return $this->generatedDataAccessSha256;
    }

    public function loadedDataAccess(): LoadedGeneratedDataAccess
    {
        return $this->loadedDataAccess;
    }

    /** @return array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public function mappingSnapshot(): array
    {
        return $this->mappingSnapshot;
    }

    /** @return array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public function generatedSnapshot(): array
    {
        return $this->generatedSnapshot;
    }
}
