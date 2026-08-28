<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryResource;

/** Carries the detached exact supported observability configuration model. */
#[Api(ApiStatus::Experimental)]
final readonly class ObservabilityConfigurationModel
{
    public function __construct(
        private string $profile,
        private OpenTelemetryResource $resource,
        private OpenTelemetryBatchPolicy $batchPolicy,
    ) {}

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
