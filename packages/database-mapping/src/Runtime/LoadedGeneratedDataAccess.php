<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries detached identities and class metadata for one exact loaded generated file. */
#[Api(ApiStatus::Experimental)]
final readonly class LoadedGeneratedDataAccess
{
    /** @param list<array{record:string,mapper:string,repository:string}> $classes */
    public function __construct(
        private string $generatedFileSha256,
        private string $mappingArtifactSha256,
        private string $generatedNamespace,
        private array $classes,
    ) {}

    public function generatedFileSha256(): string
    {
        return $this->generatedFileSha256;
    }

    public function mappingArtifactSha256(): string
    {
        return $this->mappingArtifactSha256;
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
