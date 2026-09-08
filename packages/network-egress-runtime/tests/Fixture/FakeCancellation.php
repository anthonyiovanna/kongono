<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Fixture;

use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;
use RuntimeException;

final class FakeCancellation implements EgressCancellation
{
    public int $calls = 0;

    /** @param list<bool> $answers */
    public function __construct(private array $answers = [], private ?int $throwAt = null) {}

    public function isCancellationRequested(): bool
    {
        ++$this->calls;
        if ($this->calls === $this->throwAt) {
            throw new RuntimeException('secret cancellation failure');
        }

        return $this->answers[$this->calls - 1] ?? false;
    }
}
