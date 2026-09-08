<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;

/** Tracks nondecreasing caller-supplied time without reading a wall clock. */
#[Api(ApiStatus::Internal)]
final class BudgetMeter
{
    private int $last;

    public function __construct(private readonly int $startedAt)
    {
        if ($startedAt < 0) {
            throw InvalidEgressRuntimeException::invalid('clock_negative');
        }
        $this->last = $startedAt;
    }

    public function observe(int $milliseconds): int
    {
        if ($milliseconds < 0) {
            throw InvalidEgressRuntimeException::invalid('clock_negative');
        }
        if ($milliseconds < $this->last) {
            throw InvalidEgressRuntimeException::invalid('clock_regression');
        }
        $this->last = $milliseconds;

        return $this->elapsedMilliseconds();
    }

    public function elapsedMilliseconds(): int
    {
        return $this->last - $this->startedAt;
    }
}
