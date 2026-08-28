<?php

declare(strict_types=1);

namespace Kongono\Metrics\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Diagnostic\MetricsDiagnostic;

/** Reports invalid predeclared dimension, instrument, and plan schemas. */
#[Api(ApiStatus::Experimental)]
final class InvalidMetricSchemaException extends MetricsException
{
    public static function dimension(): self
    {
        return new self(MetricsDiagnostic::Schema);
    }

    public static function instrument(): self
    {
        return new self(MetricsDiagnostic::Schema);
    }

    public static function plan(): self
    {
        return new self(MetricsDiagnostic::Schema);
    }
}
