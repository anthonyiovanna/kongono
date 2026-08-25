<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Stores one detached canonical mapping artifact and its exact identities. */
#[Api(ApiStatus::Experimental)]
final readonly class MappingArtifact
{
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private string $sourceSha256,
        private string $modelSha256,
        private string $sourceSetSha256,
        private string $builderImplementationSha256,
    ) {}

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }

    public function modelSha256(): string
    {
        return $this->modelSha256;
    }

    public function sourceSetSha256(): string
    {
        return $this->sourceSetSha256;
    }

    public function builderImplementationSha256(): string
    {
        return $this->builderImplementationSha256;
    }
}
