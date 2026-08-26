<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries validated data-access bytes without executing generated PHP. */
#[Api(ApiStatus::Experimental)]
final readonly class PreparedDataAccessBuild
{
    /**
     * @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $mappingSnapshot
     * @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $generatedSnapshot
     */
    public function __construct(
        private string $applicationRoot,
        private string $mappingSourceRelativePath,
        private string $mappingArtifactSha256,
        private string $generatedDataAccessSha256,
        private array $mappingSnapshot,
        private array $generatedSnapshot,
    ) {}

    public function applicationRoot(): string
    {
        return $this->applicationRoot;
    }

    public function mappingSourceRelativePath(): string
    {
        return $this->mappingSourceRelativePath;
    }

    public function mappingArtifactSha256(): string
    {
        return $this->mappingArtifactSha256;
    }

    public function generatedDataAccessSha256(): string
    {
        return $this->generatedDataAccessSha256;
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
