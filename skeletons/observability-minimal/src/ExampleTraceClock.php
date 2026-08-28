<?php

declare(strict_types=1);

namespace KongonoObservabilityMinimal;

use Kongono\Tracing\TraceClock;

/** Deterministic local clock for this example only; replace it in a real application. */
final class ExampleTraceClock implements TraceClock
{
    private int $tick = 0;

    public function nowNanoseconds(): int
    {
        return ++$this->tick;
    }
}
