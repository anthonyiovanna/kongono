<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OpenTelemetry\Diagnostic\OpenTelemetryDiagnostic;
use Throwable;

/** Reports rejected profile configuration without retaining rejected values. */
#[Api(ApiStatus::Internal)]
final class InvalidOpenTelemetryConfigurationException extends OpenTelemetryException
{
    public static function invalid(?Throwable $previous = null): self
    {
        return new self(OpenTelemetryDiagnostic::Configuration, $previous);
    }
}
