<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the four exact synchronous metric aggregation contracts. */
#[Api(ApiStatus::Experimental)]
enum MetricInstrumentKind: string
{
    case Counter = 'counter';
    case UpDownCounter = 'up_down_counter';
    case Gauge = 'gauge';
    case Histogram = 'histogram';
}
