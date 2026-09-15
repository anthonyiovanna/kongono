<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries bounded write-free expected native build evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class PlannedAssetApplicationBuild
{
    public function __construct(
        private string $sourceSetIdentity,
        private string $graphIdentity,
        private string $buildIdentity,
        private string $resolutionIdentity,
        private string $artifactBasename,
        private int $declarationCount,
        private int $dependencyCount,
        private int $entryPointCount,
        private int $outputCount,
        private bool $publicationRequired,
    ) {}

    public function sourceSetIdentity(): string
    {
        return $this->sourceSetIdentity;
    }
    public function graphIdentity(): string
    {
        return $this->graphIdentity;
    }
    public function buildIdentity(): string
    {
        return $this->buildIdentity;
    }
    public function resolutionIdentity(): string
    {
        return $this->resolutionIdentity;
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
    public function publicationRequired(): bool
    {
        return $this->publicationRequired;
    }
}
