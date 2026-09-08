<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Fixture;

use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;

final class FakeClock implements EgressMonotonicClock
{
    private int $calls = 0;

    /** @param non-empty-list<int> $values */
    public function __construct(private array $values = [0]) {}

    public function milliseconds(): int
    {
        $position = min($this->calls, count($this->values) - 1);
        ++$this->calls;

        return $this->values[$position];
    }
}
