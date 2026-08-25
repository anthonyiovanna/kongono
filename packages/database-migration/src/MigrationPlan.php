<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;

/** Describes one pure validated migration execution without retaining a database resource. */
#[Api(ApiStatus::Experimental)]
final readonly class MigrationPlan
{
    /** @var list<PlannedMigration> */
    private array $migrations;

    /** @param array<array-key, mixed> $migrations */
    public function __construct(
        private MigrationDirection $direction,
        private int $previousAppliedCount,
        private int $resultingAppliedCount,
        array $migrations,
        private int $operationCount,
        private MigrationRisk $risk,
        private string $catalogSha256,
    ) {
        if ($previousAppliedCount < 0 || $previousAppliedCount > 4_096
            || $resultingAppliedCount < 0 || $resultingAppliedCount > 4_096
            || $operationCount < 0 || $operationCount > 65_535
            || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $catalogSha256) || !array_is_list($migrations)) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
        $detached = [];
        foreach ($migrations as $migration) {
            if (!$migration instanceof PlannedMigration || $migration->direction() !== $direction) {
                throw InvalidMigrationDefinitionException::invalidDefinition();
            }
            $detached[] = $migration;
        }
        $changed = count($detached);
        $derivedOperationCount = array_sum(array_map(
            static fn(PlannedMigration $migration): int => count($migration->plan()->operations()),
            $detached,
        ));
        $derivedRisk = MigrationRisk::Safe;
        foreach ($detached as $index => $migration) {
            if (MigrationRisk::Destructive === $migration->plan()->risk()) {
                $derivedRisk = MigrationRisk::Destructive;
            }
            $expectedOrdinal = MigrationDirection::Apply === $direction
                ? $previousAppliedCount + $index + 1
                : $previousAppliedCount;
            if ($migration->definition()->ordinal() !== $expectedOrdinal) {
                throw InvalidMigrationDefinitionException::invalidDefinition();
            }
        }
        $expectedResult = MigrationDirection::Apply === $direction
            ? $previousAppliedCount + $changed
            : $previousAppliedCount - $changed;
        if ($resultingAppliedCount !== $expectedResult || $derivedOperationCount !== $operationCount
            || $derivedRisk !== $risk || (MigrationDirection::Revert === $direction && $changed > 1)) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
        $this->migrations = $detached;
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

    /** @return list<PlannedMigration> */
    public function migrations(): array
    {
        return [...$this->migrations];
    }

    public function operationCount(): int
    {
        return $this->operationCount;
    }

    public function risk(): MigrationRisk
    {
        return $this->risk;
    }

    public function catalogSha256(): string
    {
        return $this->catalogSha256;
    }

    public function isNoOp(): bool
    {
        return [] === $this->migrations;
    }
}
