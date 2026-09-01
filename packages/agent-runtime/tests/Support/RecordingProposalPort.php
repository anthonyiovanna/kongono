<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Support;

use Closure;
use Kongono\AgentRuntime\Proposal\AgentProposalPort;
use Kongono\AgentRuntime\Proposal\AgentProposalRequest;
use Kongono\AgentRuntime\Proposal\AgentProposalResult;
use Throwable;

final class RecordingProposalPort implements AgentProposalPort
{
    public int $proposeCalls = 0;
    public int $closeCalls = 0;

    /** @param AgentProposalResult|Closure(AgentProposalRequest):AgentProposalResult|Throwable $result */
    public function __construct(
        private AgentProposalResult|Closure|Throwable $result,
        private ?Throwable $closeFailure = null,
        private ?Closure $record = null,
    ) {}

    public function propose(AgentProposalRequest $request): AgentProposalResult
    {
        ++$this->proposeCalls;
        if (null !== $this->record) {
            ($this->record)('proposal:propose');
        }
        if ($this->result instanceof Throwable) {
            throw $this->result;
        }
        return $this->result instanceof Closure ? ($this->result)($request) : $this->result;
    }

    public function close(): void
    {
        ++$this->closeCalls;
        if (null !== $this->record) {
            ($this->record)('proposal:close');
        }
        if ($this->closeFailure instanceof Throwable) {
            throw $this->closeFailure;
        }
    }
}
