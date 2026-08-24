<?php

declare(strict_types=1);

namespace Kongono\Composition\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries the exact checked content-addressed outputs required at startup. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentApplicationBuild
{
    public function __construct(
        private string $compositionArtifactPath,
        private string $compositionArtifactBytes,
        private string $artifactSha256,
        private string $generatedWiringSha256,
        private string $generatedPhpPath,
    ) {}

    public function compositionArtifactPath(): string
    {
        return $this->compositionArtifactPath;
    }

    public function compositionArtifactBytes(): string
    {
        return $this->compositionArtifactBytes;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function generatedWiringSha256(): string
    {
        return $this->generatedWiringSha256;
    }

    public function generatedPhpPath(): string
    {
        return $this->generatedPhpPath;
    }
}
