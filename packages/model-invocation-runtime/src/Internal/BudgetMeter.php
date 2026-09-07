<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationRuntime\Exception\InvalidInvocationRuntimeException;

/** Meters only explicit integer time and retained normalized content bytes. */
#[Api(ApiStatus::Internal)]
final class BudgetMeter
{
    private int $lastMilliseconds;
    private int $elapsedMilliseconds = 0;
    private int $retainedEvidenceBytes = 0;

    public function __construct(
        private readonly InvocationExecutionBudget $budget,
        private readonly int $initialMilliseconds,
    ) {
        if ($initialMilliseconds < 0) {
            throw InvalidInvocationRuntimeException::invalid('clock_negative');
        }
        $this->lastMilliseconds = $initialMilliseconds;
    }

    public function observeTime(int $milliseconds): void
    {
        if ($milliseconds < 0) {
            throw InvalidInvocationRuntimeException::invalid('clock_negative');
        }
        if ($milliseconds < $this->lastMilliseconds || $milliseconds < $this->initialMilliseconds) {
            throw InvalidInvocationRuntimeException::invalid('clock_regression');
        }
        $this->lastMilliseconds = $milliseconds;
        $this->elapsedMilliseconds = $milliseconds - $this->initialMilliseconds;
    }

    public function durationExceeded(): bool
    {
        return $this->elapsedMilliseconds > $this->budget->maxDurationMilliseconds();
    }

    public function retain(int $bytes): bool
    {
        if ($bytes < 0 || $bytes > PHP_INT_MAX - $this->retainedEvidenceBytes) {
            return false;
        }
        $this->retainedEvidenceBytes += $bytes;

        return $this->retainedEvidenceBytes <= $this->budget->maxRetainedEvidenceBytes();
    }

    public function elapsedMilliseconds(): int
    {
        return $this->elapsedMilliseconds;
    }

    public function retainedEvidenceBytes(): int
    {
        return $this->retainedEvidenceBytes;
    }

    public function retainedLimit(): int
    {
        return $this->budget->maxRetainedEvidenceBytes();
    }

}
