<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Fixture;

use Kongono\ExternalServiceCredentialRuntime\Clock\CredentialMonotonicClock;

final class FakeCredentialMonotonicClock implements CredentialMonotonicClock
{
    public int $calls = 0;

    /** @param non-empty-list<int> $values */
    public function __construct(private array $values = [0]) {}

    public function milliseconds(): int
    {
        $position = min($this->calls, count($this->values) - 1);
        ++$this->calls;

        return $this->values[$position];
    }
}
