<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OpenTelemetry\Diagnostic\OpenTelemetryDiagnostic;

/** Reports visible no-drop bounded queue exhaustion. */
#[Api(ApiStatus::Internal)]
final class OpenTelemetryBackpressureException extends OpenTelemetryException
{
    public static function full(int $queueCount, int $queueCapacity): self
    {
        return new self(OpenTelemetryDiagnostic::Backpressure, queueCount: $queueCount, queueCapacity: $queueCapacity, stage: 'record');
    }
}
