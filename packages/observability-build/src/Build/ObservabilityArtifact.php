<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryResource;

/** Stores one immutable canonical observability configuration artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class ObservabilityArtifact
{
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private string $sourceSha256,
        private string $modelSha256,
        private string $builderImplementationSha256,
        private string $profile,
        private OpenTelemetryResource $resource,
        private OpenTelemetryBatchPolicy $batchPolicy,
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

    public function builderImplementationSha256(): string
    {
        return $this->builderImplementationSha256;
    }

    public function profile(): string
    {
        return $this->profile;
    }

    public function resource(): OpenTelemetryResource
    {
        return $this->resource;
    }

    public function batchPolicy(): OpenTelemetryBatchPolicy
    {
        return $this->batchPolicy;
    }
}
