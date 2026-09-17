<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries detached relative publication evidence without exposing its root. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfilePublication
{
    public function __construct(
        private CacheProfileArtifactIdentity $artifactIdentity,
        private string $relativeDirectory,
        private string $relativeArtifactPath,
        private bool $identicalContentExisted,
    ) {}

    public function artifactIdentity(): CacheProfileArtifactIdentity
    {
        return $this->artifactIdentity;
    }

    public function relativeDirectory(): string
    {
        return $this->relativeDirectory;
    }

    public function relativeArtifactPath(): string
    {
        return $this->relativeArtifactPath;
    }

    public function identicalContentExisted(): bool
    {
        return $this->identicalContentExisted;
    }
}
