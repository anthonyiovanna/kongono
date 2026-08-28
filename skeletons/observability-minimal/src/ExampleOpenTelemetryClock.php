<?php

declare(strict_types=1);

namespace KongonoObservabilityMinimal;

use Kongono\OpenTelemetry\OpenTelemetryClock;

/** Deterministic epoch clock for this example only; it is not a production clock. */
final class ExampleOpenTelemetryClock implements OpenTelemetryClock
{
    private int $epochNanoseconds = 1_000_000;

    public function epochNanoseconds(): int
    {
        return ++$this->epochNanoseconds;
    }
}
