<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Fixture;

use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;
use Throwable;

final class FakeInvocationMonotonicClock implements InvocationMonotonicClock
{
    public int $calls = 0;

    /** @param non-empty-list<int|Throwable> $values */
    public function __construct(private array $values = [0]) {}

    public function milliseconds(): int
    {
        $index = min($this->calls, count($this->values) - 1);
        ++$this->calls;
        $value = $this->values[$index];
        if ($value instanceof Throwable) {
            throw $value;
        }

        return $value;
    }
}
