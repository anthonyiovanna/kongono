<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Tests\Support;

use Closure;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterEmission;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterSession;
use Throwable;

final class RecordingInvocationAdapterSession implements InvocationAdapterSession
{
    public int $nextCalls = 0;
    public int $closeCalls = 0;

    /** @param list<InvocationAdapterEmission|Throwable|null> $emissions */
    public function __construct(private array $emissions, private ?Throwable $closeFailure = null, private ?Closure $onNext = null) {}

    public function next(): ?InvocationAdapterEmission
    {
        ++$this->nextCalls;
        if (null !== $this->onNext) {
            ($this->onNext)();
        }
        $emission = array_shift($this->emissions);
        if ($emission instanceof Throwable) {
            throw $emission;
        }

        return $emission;
    }

    public function close(): void
    {
        ++$this->closeCalls;
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
    }
}
