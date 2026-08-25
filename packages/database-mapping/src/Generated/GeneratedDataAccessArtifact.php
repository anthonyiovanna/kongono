<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Stores detached canonical generated PHP and its exact provenance identities. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedDataAccessArtifact
{
    /** @param list<array{record:string,mapper:string,repository:string}> $classes */
    public function __construct(
        private string $canonicalBytes,
        private string $generatedFileSha256,
        private string $mappingArtifactSha256,
        private string $modelSha256,
        private string $generatorImplementationSha256,
        private string $generatedNamespace,
        private array $classes,
    ) {}

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function generatedFileSha256(): string
    {
        return $this->generatedFileSha256;
    }

    public function mappingArtifactSha256(): string
    {
        return $this->mappingArtifactSha256;
    }

    public function modelSha256(): string
    {
        return $this->modelSha256;
    }

    public function generatorImplementationSha256(): string
    {
        return $this->generatorImplementationSha256;
    }

    public function generatedNamespace(): string
    {
        return $this->generatedNamespace;
    }

    /** @return list<array{record:string,mapper:string,repository:string}> */
    public function classes(): array
    {
        return array_map(static fn(array $class): array => [...$class], $this->classes);
    }
}
