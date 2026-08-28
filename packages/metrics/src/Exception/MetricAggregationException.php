<?php

declare(strict_types=1);

namespace Kongono\Metrics\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Diagnostic\MetricsDiagnostic;

/** Reports an unrepresentable aggregate without partial mutation. */
#[Api(ApiStatus::Experimental)]
final class MetricAggregationException extends MetricsException
{
    public static function overflow(): self
    {
        return new self(MetricsDiagnostic::Aggregation);
    }
}
