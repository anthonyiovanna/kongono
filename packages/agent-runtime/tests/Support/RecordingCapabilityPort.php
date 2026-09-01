<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Support;

use Closure;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentRuntime\Capability\AgentCapabilityInvocation;
use Kongono\AgentRuntime\Capability\AgentCapabilityPort;
use Kongono\AgentRuntime\Capability\AgentCapabilityResult;
use Throwable;

final class RecordingCapabilityPort implements AgentCapabilityPort
{
    public int $invokeCalls = 0;
    public int $closeCalls = 0;

    /** @param AgentCapabilityResult|Closure(AgentCapabilityInvocation):AgentCapabilityResult|Throwable $result */
    public function __construct(
        private CapabilityIdentity $identity,
        private AgentCapabilityResult|Closure|Throwable $result,
        private ?Throwable $closeFailure = null,
        private ?Closure $record = null,
    ) {}

    public function capabilityIdentity(): CapabilityIdentity
    {
        return $this->identity;
    }

    public function invoke(AgentCapabilityInvocation $invocation): AgentCapabilityResult
    {
        ++$this->invokeCalls;
        if (null !== $this->record) {
            ($this->record)($this->identity->value() . ':invoke');
        }
        if ($this->result instanceof Throwable) {
            throw $this->result;
        }
        return $this->result instanceof Closure ? ($this->result)($invocation) : $this->result;
    }

    public function close(): void
    {
        ++$this->closeCalls;
        if (null !== $this->record) {
            ($this->record)($this->identity->value() . ':close');
        }
        if ($this->closeFailure instanceof Throwable) {
            throw $this->closeFailure;
        }
    }
}
