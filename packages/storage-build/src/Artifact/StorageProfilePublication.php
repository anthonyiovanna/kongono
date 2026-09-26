<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries detached evidence for one contained storage-profile publication. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageProfilePublication
{
    public function __construct(
        private string $publicationRoot,
        private string $absoluteArtifactPath,
        private string $relativeArtifactPath,
        private StorageProfileArtifactIdentity $artifactIdentity,
        private int $artifactByteCount,
        private bool $created,
    ) {}

    public function publicationRoot(): string
    {
        return $this->publicationRoot;
    }

    public function absoluteArtifactPath(): string
    {
        return $this->absoluteArtifactPath;
    }

    public function artifactIdentity(): StorageProfileArtifactIdentity
    {
        return $this->artifactIdentity;
    }

    public function relativeArtifactPath(): string
    {
        return $this->relativeArtifactPath;
    }

    public function artifactByteCount(): int
    {
        return $this->artifactByteCount;
    }

    public function created(): bool
    {
        return $this->created;
    }
}
