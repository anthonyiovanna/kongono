<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Fixture;

use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Throwable;

final class FakeInvocationCancellation implements InvocationCancellation
{
    public int $calls = 0;

    /** @param non-empty-list<bool|Throwable> $values */
    public function __construct(private array $values = [false]) {}

    public function isCancellationRequested(): bool
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
