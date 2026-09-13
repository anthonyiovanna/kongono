<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Definition\ViewApplicationDefinition;
use Kongono\ViewBuild\Artifact\CompiledViewArtifact;

/** Carries one exact-current artifact and its canonical application-owned locations. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentViewApplicationBuild
{
    public function __construct(private ViewApplicationDefinition $definition, private CompiledViewArtifact $artifact) {}

    public function definition(): ViewApplicationDefinition
    {
        return $this->definition;
    }
    public function artifact(): CompiledViewArtifact
    {
        return $this->artifact;
    }
    public function artifactBasename(): string
    {
        return 'kongono-view-v1-' . $this->artifact->compiledViewIdentity()->value();
    }
    public function artifactDirectory(): string
    {
        return $this->definition->artifactOutputRoot() . '/' . $this->artifactBasename();
    }
    public function sourceIdentity(): \Kongono\ViewBuild\Source\ViewSourceIdentity
    {
        return $this->artifact->sourceIdentity();
    }
    public function compiledViewIdentity(): \Kongono\ViewContract\Identity\CompiledViewIdentity
    {
        return $this->artifact->compiledViewIdentity();
    }
    public function modelSha256(): string
    {
        return $this->artifact->modelSha256();
    }
    public function generatedPhpSha256(): string
    {
        return $this->artifact->generatedPhpSha256();
    }
    public function viewCount(): int
    {
        return $this->artifact->viewCount();
    }
}
