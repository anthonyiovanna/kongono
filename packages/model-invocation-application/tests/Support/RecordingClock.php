<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Tests\Support;

use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;

final class RecordingClock implements InvocationMonotonicClock
{
    public int $calls = 0;

    /** @param non-empty-list<int> $values */
    public function __construct(private array $values = [0]) {}

    public function milliseconds(): int
    {
        $index = min($this->calls, count($this->values) - 1);
        ++$this->calls;

        return $this->values[$index];
    }
}
