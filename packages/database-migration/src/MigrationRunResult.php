<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;

/** Exposes bounded execution counts without SQL, identifiers, checksums, or provider details. */
#[Api(ApiStatus::Experimental)]
final readonly class MigrationRunResult
{
    public function __construct(
        private MigrationDirection $direction,
        private int $previousAppliedCount,
        private int $resultingAppliedCount,
        private int $changedMigrationCount,
        private int $executedOperationCount,
    ) {
        $expectedResult = MigrationDirection::Apply === $direction
            ? $previousAppliedCount + $changedMigrationCount
            : $previousAppliedCount - $changedMigrationCount;
        if (min($previousAppliedCount, $resultingAppliedCount, $changedMigrationCount, $executedOperationCount) < 0
            || max($previousAppliedCount, $resultingAppliedCount, $changedMigrationCount) > 4_096
            || $executedOperationCount > 65_535
            || $resultingAppliedCount !== $expectedResult
            || (MigrationDirection::Revert === $direction && $changedMigrationCount > 1)
            || (0 === $changedMigrationCount) !== (0 === $executedOperationCount)) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
    }

    public function direction(): MigrationDirection
    {
        return $this->direction;
    }

    public function previousAppliedCount(): int
    {
        return $this->previousAppliedCount;
    }

    public function resultingAppliedCount(): int
    {
        return $this->resultingAppliedCount;
    }

    public function changedMigrationCount(): int
    {
        return $this->changedMigrationCount;
    }

    public function executedOperationCount(): int
    {
        return $this->executedOperationCount;
    }

    public function isNoOp(): bool
    {
        return 0 === $this->changedMigrationCount;
    }
}
