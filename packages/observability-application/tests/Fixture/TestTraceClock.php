<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Fixture;

use Kongono\Tracing\TraceClock;

final class TestTraceClock implements TraceClock
{
    private int $tick = 0;

    public function nowNanoseconds(): int
    {
        return ++$this->tick;
    }
}
