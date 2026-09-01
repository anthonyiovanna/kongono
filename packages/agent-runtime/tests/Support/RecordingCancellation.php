<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Support;

use Kongono\AgentRuntime\Cancellation\AgentCancellation;
use Throwable;

final class RecordingCancellation implements AgentCancellation
{
    public int $calls = 0;
    /** @param non-empty-list<bool|Throwable> $observations */
    public function __construct(private array $observations = [false, false, false, false]) {}

    public function isCancellationRequested(): bool
    {
        $value = $this->observations[min($this->calls, count($this->observations) - 1)];
        ++$this->calls;
        if ($value instanceof Throwable) {
            throw $value;
        }
        return $value;
    }
}
