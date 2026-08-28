<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Fixture;

use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceId;
use Kongono\Tracing\TraceIdentifierSource;

final class TestTraceIdentifierSource implements TraceIdentifierSource
{
    private int $trace = 0;
    private int $span = 0;

    public function nextTraceId(): TraceId
    {
        return new TraceId(sprintf('%032x', ++$this->trace));
    }

    public function nextSpanId(): SpanId
    {
        return new SpanId(sprintf('%016x', ++$this->span));
    }
}
