<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Tests\Support;

use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;
use RuntimeException;

final class RecordingCancellation implements EgressCancellation
{
    public int $calls = 0;

    /** @param list<bool> $answers */
    public function __construct(private array $answers = [], private ?int $throwAt = null) {}

    public function isCancellationRequested(): bool
    {
        ++$this->calls;
        if ($this->calls === $this->throwAt) {
            throw new RuntimeException('foreign cancellation failure');
        }

        return $this->answers[$this->calls - 1] ?? false;
    }
}
