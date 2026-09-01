<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Internal;

use Kongono\AgentRuntime\Budget\AgentRuntimeBudget;
use Kongono\AgentRuntime\Budget\AgentRuntimeUsage;
use Kongono\AgentRuntime\Exception\AgentRuntimeOperationException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Meters only the fixed one-proposal/one-capability profile with overflow-safe counters. */
#[Api(ApiStatus::Internal)]
final class BudgetMeter
{
    private int $lastMilliseconds;
    private int $elapsed = 0;
    private int $steps = 0;
    private int $tokens = 0;
    private int $capabilityCalls = 0;
    private int $outputBytes = 0;
    private bool $overflowed = false;

    public function __construct(private readonly AgentRuntimeBudget $budget, private readonly int $startedMilliseconds)
    {
        if ($startedMilliseconds < 0) {
            throw AgentRuntimeOperationException::failed('clock');
        }
        $this->lastMilliseconds = $startedMilliseconds;
    }

    public function checkpoint(int $milliseconds): void
    {
        if ($milliseconds < 0 || $milliseconds < $this->lastMilliseconds) {
            throw AgentRuntimeOperationException::failed('clock');
        }
        $this->lastMilliseconds = $milliseconds;
        $this->elapsed = $milliseconds - $this->startedMilliseconds;
    }

    public function canBeginProposal(): bool
    {
        return !$this->exceeded() && $this->steps < $this->budget->stepLimit();
    }

    public function recordProposal(int $tokens, int $outputBytes): void
    {
        $this->steps = self::safeAdd($this->steps, 1, $this->overflowed);
        $this->tokens = self::safeAdd($this->tokens, $tokens, $this->overflowed);
        $this->outputBytes = self::safeAdd($this->outputBytes, $outputBytes, $this->overflowed);
    }

    public function canBeginCapability(): bool
    {
        return !$this->exceeded()
            && $this->steps < $this->budget->stepLimit()
            && $this->capabilityCalls < $this->budget->capabilityCallLimit();
    }

    public function recordCapability(int $outputBytes): void
    {
        $this->steps = self::safeAdd($this->steps, 1, $this->overflowed);
        $this->capabilityCalls = self::safeAdd($this->capabilityCalls, 1, $this->overflowed);
        $this->outputBytes = self::safeAdd($this->outputBytes, $outputBytes, $this->overflowed);
    }

    public function exceeded(): bool
    {
        return $this->overflowed
            || $this->steps > $this->budget->stepLimit()
            || $this->elapsed > $this->budget->durationMillisecondsLimit()
            || $this->tokens > $this->budget->tokenLimit()
            || $this->capabilityCalls > $this->budget->capabilityCallLimit()
            || $this->outputBytes > $this->budget->outputBytesLimit();
    }

    public function usage(): AgentRuntimeUsage
    {
        return new AgentRuntimeUsage($this->steps, $this->elapsed, $this->tokens, $this->capabilityCalls, 0, 0, $this->outputBytes, 0, 1);
    }

    private static function safeAdd(int $left, int $right, bool &$overflowed): int
    {
        if ($right < 0 || $left > PHP_INT_MAX - $right) {
            $overflowed = true;
            return PHP_INT_MAX;
        }
        return $left + $right;
    }
}
