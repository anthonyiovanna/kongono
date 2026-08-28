<?php

declare(strict_types=1);

namespace Kongono\Metrics\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Diagnostic\MetricsDiagnostic;

/** Reports invalid collection state or exact instrument ownership. */
#[Api(ApiStatus::Experimental)]
final class MetricLifecycleException extends MetricsException
{
    public static function state(): self
    {
        return new self(MetricsDiagnostic::Lifecycle);
    }

    public static function ownership(): self
    {
        return new self(MetricsDiagnostic::Lifecycle);
    }
}
