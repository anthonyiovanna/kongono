<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Fixture;

use Closure;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportSession;
use Throwable;

final class FakeEgressTransportSession implements EgressTransportSession
{
    public int $nextCalls = 0;
    public int $closeCalls = 0;

    /** @param list<?EgressTransportEmission> $emissions */
    public function __construct(private array $emissions, private ?Closure $observer = null, private ?Throwable $closeFailure = null) {}

    public function next(): ?EgressTransportEmission
    {
        ++$this->nextCalls;
        if (null !== $this->observer) {
            ($this->observer)('transport.next');
        }

        return array_shift($this->emissions);
    }

    public function close(): void
    {
        ++$this->closeCalls;
        if (null !== $this->observer) {
            ($this->observer)('transport.close');
        }
        if ($this->closeFailure instanceof Throwable) {
            throw $this->closeFailure;
        }
    }
}
