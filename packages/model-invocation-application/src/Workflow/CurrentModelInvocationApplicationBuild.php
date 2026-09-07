<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Generated\GeneratedModelInvocationWiring;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationApplication\Runtime\LoadedModelInvocationWiring;

/** Returns detached exact-current source, generated, and one-start load evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentModelInvocationApplicationBuild
{
    /**
     * @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,int>} $artifactSnapshot
     * @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,int>} $generatedSnapshot
     */
    public function __construct(
        private string $applicationRoot,
        private ModelInvocationApplicationProfile $profile,
        private GeneratedModelInvocationWiring $generatedWiring,
        private LoadedModelInvocationWiring $loadedWiring,
        private array $artifactSnapshot,
        private array $generatedSnapshot,
    ) {}

    public function applicationRoot(): string
    {
        return $this->applicationRoot;
    }
    public function profile(): ModelInvocationApplicationProfile
    {
        return $this->profile;
    }
    public function generatedWiring(): GeneratedModelInvocationWiring
    {
        return $this->generatedWiring;
    }
    public function loadedWiring(): LoadedModelInvocationWiring
    {
        return $this->loadedWiring;
    }
    public function invocationPlanArtifactPath(): string
    {
        return $this->artifactSnapshot['absolute'];
    }
    public function invocationPlanArtifactBytes(): string
    {
        return $this->artifactSnapshot['bytes'];
    }
    public function invocationPlanArtifactSha256(): string
    {
        return $this->artifactSnapshot['sha256'];
    }
    public function generatedWiringPath(): string
    {
        return $this->generatedSnapshot['absolute'];
    }
    public function generatedWiringBytes(): string
    {
        return $this->generatedSnapshot['bytes'];
    }
    public function generatedWiringSha256(): string
    {
        return $this->generatedSnapshot['sha256'];
    }
}
