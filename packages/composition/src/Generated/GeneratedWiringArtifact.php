<?php

declare(strict_types=1);

namespace Kongono\Composition\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Stores one detached canonical generated-wiring file and its bound identities. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedWiringArtifact
{
    public function __construct(
        private string $canonicalBytes,
        private string $wiringSha256,
        private string $compositionArtifactSha256,
        private string $modelSha256,
        private string $generatorImplementationSha256,
        private string $generatedNamespace,
        private string $applicationClass,
        private string $operationClass,
    ) {}

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }
    public function wiringSha256(): string
    {
        return $this->wiringSha256;
    }
    public function compositionArtifactSha256(): string
    {
        return $this->compositionArtifactSha256;
    }
    public function modelSha256(): string
    {
        return $this->modelSha256;
    }
    public function generatorImplementationSha256(): string
    {
        return $this->generatorImplementationSha256;
    }
    public function generatedNamespace(): string
    {
        return $this->generatedNamespace;
    }
    public function applicationClass(): string
    {
        return $this->applicationClass;
    }
    public function operationClass(): string
    {
        return $this->operationClass;
    }
}
