<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Workflow;

use Kongono\AgentApplication\Generated\GeneratedAgentWiring;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\AgentApplication\Runtime\LoadedAgentWiring;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Returns detached exact-current source, profile, generated, and one-start load evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentAgentApplicationBuild
{
    /**
     * @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} $declarationSnapshot
     * @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int,mixed>} $generatedSnapshot
     */
    public function __construct(
        private string $applicationRoot,
        private AgentApplicationProfile $profile,
        private GeneratedAgentWiring $generatedWiring,
        private LoadedAgentWiring $loadedWiring,
        private array $declarationSnapshot,
        private array $generatedSnapshot,
    ) {}

    public function applicationRoot(): string
    {
        return $this->applicationRoot;
    }
    public function profile(): AgentApplicationProfile
    {
        return $this->profile;
    }
    public function generatedWiring(): GeneratedAgentWiring
    {
        return $this->generatedWiring;
    }
    public function loadedWiring(): LoadedAgentWiring
    {
        return $this->loadedWiring;
    }
    public function declarationArtifactPath(): string
    {
        return $this->declarationSnapshot['absolute'];
    }
    public function declarationArtifactBytes(): string
    {
        return $this->declarationSnapshot['bytes'];
    }
    public function declarationArtifactSha256(): string
    {
        return $this->declarationSnapshot['sha256'];
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
