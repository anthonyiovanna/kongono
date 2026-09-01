<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Artifact;

use Kongono\AgentDeclarationBuild\Declaration\AgentDeclarationIdentity;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Stores only builder- or validator-verified detached artifact state. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentDeclarationArtifact
{
    /** @internal Constructed only from package-verified canonical state. */
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private AgentDeclarationIdentity $declarationIdentity,
        private string $declarationSha256,
    ) {}

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function declarationIdentity(): AgentDeclarationIdentity
    {
        return $this->declarationIdentity;
    }

    public function declarationSha256(): string
    {
        return $this->declarationSha256;
    }
}
