<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Budget;

use Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries all nine explicit operation ceilings without granting authority. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentRuntimeBudget
{
    public function __construct(
        private int $stepLimit,
        private int $durationMillisecondsLimit,
        private int $tokenLimit,
        private int $capabilityCallLimit,
        private int $retryLimit,
        private int $delegationDepthLimit,
        private int $outputBytesLimit,
        private int $retainedStateBytesLimit,
        private int $concurrencyLimit,
    ) {
        foreach ($this->values() as $value) {
            if ($value < 0) {
                throw InvalidAgentRuntimeException::invalid('budget_value_range');
            }
        }
    }

    public function stepLimit(): int
    {
        return $this->stepLimit;
    }
    public function durationMillisecondsLimit(): int
    {
        return $this->durationMillisecondsLimit;
    }
    public function tokenLimit(): int
    {
        return $this->tokenLimit;
    }
    public function capabilityCallLimit(): int
    {
        return $this->capabilityCallLimit;
    }
    public function retryLimit(): int
    {
        return $this->retryLimit;
    }
    public function delegationDepthLimit(): int
    {
        return $this->delegationDepthLimit;
    }
    public function outputBytesLimit(): int
    {
        return $this->outputBytesLimit;
    }
    public function retainedStateBytesLimit(): int
    {
        return $this->retainedStateBytesLimit;
    }
    public function concurrencyLimit(): int
    {
        return $this->concurrencyLimit;
    }

    /** @return list<int> */
    public function values(): array
    {
        return [$this->stepLimit, $this->durationMillisecondsLimit, $this->tokenLimit, $this->capabilityCallLimit, $this->retryLimit, $this->delegationDepthLimit, $this->outputBytesLimit, $this->retainedStateBytesLimit, $this->concurrencyLimit];
    }
}
