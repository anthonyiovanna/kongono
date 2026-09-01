<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Budget;

use Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Records bounded actual counters as operation evidence rather than billing truth. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentRuntimeUsage
{
    public function __construct(
        private int $steps,
        private int $durationMilliseconds,
        private int $tokens,
        private int $capabilityCalls,
        private int $retries,
        private int $delegationDepth,
        private int $outputBytes,
        private int $retainedStateBytes,
        private int $concurrency,
    ) {
        foreach ($this->values() as $value) {
            if ($value < 0) {
                throw InvalidAgentRuntimeException::invalid('usage_value_range');
            }
        }
    }

    public function steps(): int
    {
        return $this->steps;
    }
    public function durationMilliseconds(): int
    {
        return $this->durationMilliseconds;
    }
    public function tokens(): int
    {
        return $this->tokens;
    }
    public function capabilityCalls(): int
    {
        return $this->capabilityCalls;
    }
    public function retries(): int
    {
        return $this->retries;
    }
    public function delegationDepth(): int
    {
        return $this->delegationDepth;
    }
    public function outputBytes(): int
    {
        return $this->outputBytes;
    }
    public function retainedStateBytes(): int
    {
        return $this->retainedStateBytes;
    }
    public function concurrency(): int
    {
        return $this->concurrency;
    }

    /** @return list<int> */
    public function values(): array
    {
        return [$this->steps, $this->durationMilliseconds, $this->tokens, $this->capabilityCalls, $this->retries, $this->delegationDepth, $this->outputBytes, $this->retainedStateBytes, $this->concurrency];
    }
}
