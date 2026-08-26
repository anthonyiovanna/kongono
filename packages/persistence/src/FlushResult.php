<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;

/** Reports an atomic applied-or-zero-applied neutral flush outcome. */
#[Api(ApiStatus::Experimental)]
final readonly class FlushResult
{
    public function __construct(
        private FlushOutcome $outcome,
        private int $attemptedCount,
        private int $appliedCount,
        private int $absentCount,
        private int $conflictCount,
    ) {
        if (
            $attemptedCount < 0
            || $appliedCount < 0
            || $absentCount < 0
            || $conflictCount < 0
            || $attemptedCount > 1024
            || $appliedCount > 1024
            || $absentCount > 1024
            || $conflictCount > 1024
        ) {
            throw InvalidPersistenceValueException::validation();
        }

        $validShape = match ($outcome) {
            FlushOutcome::NoChanges => 0 === $attemptedCount && 0 === $appliedCount && 0 === $absentCount && 0 === $conflictCount,
            FlushOutcome::Applied => $attemptedCount >= 1 && $appliedCount === $attemptedCount && 0 === $absentCount && 0 === $conflictCount,
            FlushOutcome::EntityAbsent => $attemptedCount >= 1
                && 0 === $appliedCount
                && $absentCount >= 1
                && $absentCount <= $attemptedCount
                && 0 === $conflictCount,
            FlushOutcome::OptimisticConflict => $attemptedCount >= 1
                && 0 === $appliedCount
                && 0 === $absentCount
                && $conflictCount >= 1
                && $conflictCount <= $attemptedCount,
        };

        if (!$validShape) {
            throw InvalidPersistenceValueException::validation();
        }
    }

    public function outcome(): FlushOutcome
    {
        return $this->outcome;
    }

    public function attemptedCount(): int
    {
        return $this->attemptedCount;
    }

    public function appliedCount(): int
    {
        return $this->appliedCount;
    }

    public function absentCount(): int
    {
        return $this->absentCount;
    }

    public function conflictCount(): int
    {
        return $this->conflictCount;
    }
}
