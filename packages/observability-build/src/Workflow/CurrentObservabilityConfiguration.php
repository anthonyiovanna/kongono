<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Workflow;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ObservabilityBuild\Build\ObservabilityArtifact;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryResource;

/** Binds one running application to exact immutable checked configuration. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrentObservabilityConfiguration
{
    public function __construct(private ObservabilityArtifact $artifact, private string $artifactBasename) {}

    public function evidence(): ObservabilityArtifact
    {
        return $this->artifact;
    }

    public function resource(): OpenTelemetryResource
    {
        return $this->artifact->resource();
    }

    public function batchPolicy(): OpenTelemetryBatchPolicy
    {
        return $this->artifact->batchPolicy();
    }

    public function profile(): string
    {
        return $this->artifact->profile();
    }

    public function artifactBasename(): string
    {
        return $this->artifactBasename;
    }

    public function artifactSha256(): string
    {
        return $this->artifact->artifactSha256();
    }

    public function sourceSha256(): string
    {
        return $this->artifact->sourceSha256();
    }
}
