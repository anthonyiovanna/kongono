<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Tests\Support;

use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportSession;
use Throwable;

final class RecordingTransportSession implements EgressTransportSession
{
    public int $nextCalls = 0;
    public int $closeCalls = 0;

    /** @param list<?EgressTransportEmission> $emissions */
    public function __construct(private array $emissions, private ?Throwable $closeFailure = null, private mixed $onNext = null) {}

    public function next(): ?EgressTransportEmission
    {
        ++$this->nextCalls;
        if (is_callable($this->onNext)) {
            ($this->onNext)();
        }

        return array_shift($this->emissions);
    }

    public function close(): void
    {
        ++$this->closeCalls;
        if ($this->closeFailure instanceof Throwable) {
            throw $this->closeFailure;
        }
    }
}
