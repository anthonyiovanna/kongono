<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects one exact OTLP signal endpoint suffix. */
#[Api(ApiStatus::Experimental)]
enum OtlpSignal: string
{
    case Traces = '/v1/traces';
    case Metrics = '/v1/metrics';
    case Logs = '/v1/logs';

    public function endpointSuffix(): string
    {
        return $this->value;
    }
}
