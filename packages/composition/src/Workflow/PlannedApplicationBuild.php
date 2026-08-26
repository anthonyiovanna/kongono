<?php

declare(strict_types=1);

namespace Kongono\Composition\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries only the predicted content-addressed output identities for one build. */
#[Api(ApiStatus::Experimental)]
final readonly class PlannedApplicationBuild
{
    public function __construct(
        private string $compositionArtifactBasename,
        private string $generatedPhpBasename,
        private string $artifactSha256,
        private string $generatedWiringSha256,
        private bool $publicationRequired,
    ) {}

    public function compositionArtifactBasename(): string
    {
        return $this->compositionArtifactBasename;
    }

    public function generatedPhpBasename(): string
    {
        return $this->generatedPhpBasename;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function generatedWiringSha256(): string
    {
        return $this->generatedWiringSha256;
    }

    public function publicationRequired(): bool
    {
        return $this->publicationRequired;
    }
}
