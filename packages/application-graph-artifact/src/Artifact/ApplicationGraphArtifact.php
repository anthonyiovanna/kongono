<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphArtifact\Inspection\ApplicationGraphArtifactInspection;

/** Stores only detached identities, counts, and canonical artifact bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationGraphArtifact
{
    /** @internal Constructed only from validated package-owned state. */
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private GraphIdentity $graphIdentity,
        private string $graphSha256,
        private int $nodeCount,
        private int $relationCount,
        private ApplicationGraphArtifactInspection $inspection,
    ) {}

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }
    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }
    public function graphIdentity(): GraphIdentity
    {
        return $this->graphIdentity;
    }
    public function graphSha256(): string
    {
        return $this->graphSha256;
    }
    public function nodeCount(): int
    {
        return $this->nodeCount;
    }
    public function relationCount(): int
    {
        return $this->relationCount;
    }

    public function inspection(): ApplicationGraphArtifactInspection
    {
        return $this->inspection;
    }
}
