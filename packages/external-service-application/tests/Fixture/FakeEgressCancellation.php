<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Fixture;

use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;

final class FakeEgressCancellation implements EgressCancellation
{
    public int $calls = 0;

    /** @param list<bool> $answers */
    public function __construct(private array $answers = []) {}

    public function isCancellationRequested(): bool
    {
        ++$this->calls;

        return $this->answers[$this->calls - 1] ?? false;
    }
}
