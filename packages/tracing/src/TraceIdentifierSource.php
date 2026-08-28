<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Supplies caller-owned typed identifiers without a package default. */
#[Api(ApiStatus::Experimental)]
interface TraceIdentifierSource
{
    public function nextTraceId(): TraceId;

    public function nextSpanId(): SpanId;
}
