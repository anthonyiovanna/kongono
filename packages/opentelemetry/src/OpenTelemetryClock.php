<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies an explicit positive Unix epoch nanosecond enqueue timestamp. */
#[Api(ApiStatus::Experimental)]
interface OpenTelemetryClock
{
    public function epochNanoseconds(): int;
}
