<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Source\ViewSourceIdentity;
use Kongono\ViewContract\Identity\CompiledViewIdentity;

/** Carries immutable deterministic planned build evidence without paths or source bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class PlannedViewApplicationBuild
{
    public function __construct(
        private ViewSourceIdentity $sourceIdentity,
        private CompiledViewIdentity $compiledViewIdentity,
        private string $modelSha256,
        private string $generatedPhpSha256,
        private int $viewCount,
        private string $artifactBasename,
        private bool $publicationRequired,
    ) {}

    public function sourceIdentity(): ViewSourceIdentity
    {
        return $this->sourceIdentity;
    }
    public function compiledViewIdentity(): CompiledViewIdentity
    {
        return $this->compiledViewIdentity;
    }
    public function modelSha256(): string
    {
        return $this->modelSha256;
    }
    public function generatedPhpSha256(): string
    {
        return $this->generatedPhpSha256;
    }
    public function viewCount(): int
    {
        return $this->viewCount;
    }
    public function artifactBasename(): string
    {
        return $this->artifactBasename;
    }
    public function publicationRequired(): bool
    {
        return $this->publicationRequired;
    }
}
