<?php

declare(strict_types=1);

namespace Kongono\Metrics\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Diagnostic\MetricsDiagnostic;
use Throwable;

/** Preserves an external recorder failure after irrevocable collection closure. */
#[Api(ApiStatus::Experimental)]
final class MetricRecordingException extends MetricsException
{
    public static function failed(Throwable $previous): self
    {
        return new self(MetricsDiagnostic::Recording, $previous);
    }
}
