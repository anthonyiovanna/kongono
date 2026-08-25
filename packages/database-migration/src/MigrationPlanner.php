<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\DestructiveMigrationReviewRequiredException;
use Kongono\DatabaseMigration\Exception\MigrationHistoryMismatchException;
use Kongono\DatabaseMigration\Exception\MigrationNotReversibleException;

/** Computes exact-prefix ApplyPending and RevertLast plans without I/O or mutation. */
#[Api(ApiStatus::Experimental)]
final readonly class MigrationPlanner
{
    public function applyPending(
        MigrationCatalog $catalog,
        MigrationHistory $history,
        DestructiveMigrationPolicy $policy,
    ): MigrationPlan {
        $this->validatePrefix($catalog, $history, MigrationDirection::Apply);
        $definitions = array_slice($catalog->definitions(), $history->count());
        $planned = [];
        $operationCount = 0;
        $risk = MigrationRisk::Safe;
        foreach ($definitions as $definition) {
            $direction = $definition->apply();
            if (MigrationRisk::Destructive === $direction->risk()) {
                $risk = MigrationRisk::Destructive;
            }
            $operationCount += count($direction->operations());
            $planned[] = new PlannedMigration($definition, MigrationDirection::Apply);
        }
        if (MigrationRisk::Destructive === $risk && DestructiveMigrationPolicy::Deny === $policy) {
            throw DestructiveMigrationReviewRequiredException::denied(MigrationDirection::Apply);
        }

        return new MigrationPlan(
            MigrationDirection::Apply,
            $history->count(),
            $history->count() + count($planned),
            $planned,
            $operationCount,
            $risk,
            $catalog->sha256(),
        );
    }

    public function revertLast(
        MigrationCatalog $catalog,
        MigrationHistory $history,
        DestructiveMigrationPolicy $policy,
    ): MigrationPlan {
        $this->validatePrefix($catalog, $history, MigrationDirection::Revert);
        if (0 === $history->count()) {
            return new MigrationPlan(MigrationDirection::Revert, 0, 0, [], 0, MigrationRisk::Safe, $catalog->sha256());
        }
        $definition = $catalog->definitions()[$history->count() - 1] ?? throw MigrationHistoryMismatchException::mismatch(MigrationDirection::Revert);
        $revert = $definition->revert();
        if (null === $revert) {
            throw MigrationNotReversibleException::absent($definition->ordinal());
        }
        if (MigrationRisk::Destructive === $revert->risk() && DestructiveMigrationPolicy::Deny === $policy) {
            throw DestructiveMigrationReviewRequiredException::denied(MigrationDirection::Revert);
        }

        return new MigrationPlan(
            MigrationDirection::Revert,
            $history->count(),
            $history->count() - 1,
            [new PlannedMigration($definition, MigrationDirection::Revert)],
            count($revert->operations()),
            $revert->risk(),
            $catalog->sha256(),
        );
    }

    private function validatePrefix(MigrationCatalog $catalog, MigrationHistory $history, MigrationDirection $direction): void
    {
        $definitions = $catalog->definitions();
        if ($history->count() > count($definitions)) {
            throw MigrationHistoryMismatchException::mismatch($direction);
        }
        foreach ($history->records() as $index => $record) {
            $definition = $definitions[$index];
            if ($record->ordinal() !== $definition->ordinal() || $record->id() !== $definition->id()
                || $record->checksum() !== $definition->sha256()) {
                throw MigrationHistoryMismatchException::mismatch($direction);
            }
        }
    }
}
