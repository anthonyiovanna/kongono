<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OpenTelemetry\Diagnostic\OpenTelemetryDiagnostic;
use Throwable;

/** Reports unsupported or malformed signal mapping without raw signal disclosure. */
#[Api(ApiStatus::Internal)]
final class OpenTelemetryMappingException extends OpenTelemetryException
{
    public static function invalid(?Throwable $previous = null, ?string $signalKind = null): self
    {
        return new self(OpenTelemetryDiagnostic::Mapping, $previous, $signalKind, stage: 'mapping');
    }
}
