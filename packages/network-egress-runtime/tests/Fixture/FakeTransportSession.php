<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Fixture;

use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportSession;
use RuntimeException;

final class FakeTransportSession implements EgressTransportSession
{
    public int $nextCalls = 0;
    public int $closeCalls = 0;

    /** @param list<?EgressTransportEmission> $emissions */
    public function __construct(private array $emissions, private bool $closeFails = false, private mixed $beforeNext = null) {}

    public function next(): ?EgressTransportEmission
    {
        ++$this->nextCalls;
        if (is_callable($this->beforeNext)) {
            ($this->beforeNext)();
        }

        return array_shift($this->emissions);
    }

    public function close(): void
    {
        ++$this->closeCalls;
        if ($this->closeFails) {
            throw new RuntimeException('secret close failure');
        }
    }
}
