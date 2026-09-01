<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Support;

use Kongono\AgentRuntime\Clock\AgentMonotonicClock;
use Throwable;

final class RecordingClock implements AgentMonotonicClock
{
    public int $calls = 0;
    /** @param non-empty-list<int|Throwable> $values */
    public function __construct(private array $values = [0, 0, 0, 0, 0]) {}

    public function milliseconds(): int
    {
        $value = $this->values[min($this->calls, count($this->values) - 1)];
        ++$this->calls;
        if ($value instanceof Throwable) {
            throw $value;
        }
        return $value;
    }
}
