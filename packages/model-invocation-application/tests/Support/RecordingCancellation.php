<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Tests\Support;

use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;

final class RecordingCancellation implements InvocationCancellation
{
    public int $calls = 0;

    /** @param non-empty-list<bool> $values */
    public function __construct(private array $values = [false]) {}

    public function isCancellationRequested(): bool
    {
        $index = min($this->calls, count($this->values) - 1);
        ++$this->calls;

        return $this->values[$index];
    }
}
