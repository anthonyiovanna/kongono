<?php

declare(strict_types=1);

namespace Kongono\Metrics\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Diagnostic\MetricsDiagnostic;

/** Reports invalid bounded metric scalar and boundary values. */
#[Api(ApiStatus::Experimental)]
final class InvalidMetricValueException extends MetricsException
{
    public static function unit(): self
    {
        return new self(MetricsDiagnostic::Value);
    }

    public static function measurement(): self
    {
        return new self(MetricsDiagnostic::Value);
    }

    public static function boundaries(): self
    {
        return new self(MetricsDiagnostic::Value);
    }
}
