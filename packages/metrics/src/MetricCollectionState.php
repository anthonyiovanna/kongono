<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the explicit terminal lifecycle of one metric collection. */
#[Api(ApiStatus::Experimental)]
enum MetricCollectionState: string
{
    case Active = 'active';
    case Collected = 'collected';
    case Retired = 'retired';
}
