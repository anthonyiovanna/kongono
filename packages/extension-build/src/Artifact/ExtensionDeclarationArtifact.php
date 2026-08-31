<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionBuild\Identity\DeclarationSetIdentity;

/** Stores only detached declaration identities, counts, and canonical artifact bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionDeclarationArtifact
{
    /** @internal Constructed only from validated package-owned state. */
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private DeclarationSetIdentity $declarationSetIdentity,
        private string $declarationSetSha256,
        private int $declarationCount,
    ) {}

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function declarationSetIdentity(): DeclarationSetIdentity
    {
        return $this->declarationSetIdentity;
    }

    public function declarationSetSha256(): string
    {
        return $this->declarationSetSha256;
    }

    public function declarationCount(): int
    {
        return $this->declarationCount;
    }
}
