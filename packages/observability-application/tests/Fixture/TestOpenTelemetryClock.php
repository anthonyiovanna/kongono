<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Fixture;

use Kongono\OpenTelemetry\OpenTelemetryClock;

final class TestOpenTelemetryClock implements OpenTelemetryClock
{
    private int $tick = 100;

    public function epochNanoseconds(): int
    {
        return ++$this->tick;
    }
}
