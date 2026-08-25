<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\MigrationDirection;
use Kongono\DatabaseMigration\MigrationRisk;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing version-1 migration diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum MigrationDiagnostic: string
{
    case InvalidDefinition = 'KGN-MIGRATION-1001';
    case HistoryMismatch = 'KGN-MIGRATION-1002';
    case DestructiveReviewRequired = 'KGN-MIGRATION-1003';
    case NotReversible = 'KGN-MIGRATION-1004';
    case LockUnavailable = 'KGN-MIGRATION-1005';
    case Execution = 'KGN-MIGRATION-1006';
    case UncertainOrCleanup = 'KGN-MIGRATION-1007';

    public function category(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'invalid_definition',
            self::HistoryMismatch => 'history_mismatch',
            self::DestructiveReviewRequired => 'destructive_review_required',
            self::NotReversible => 'not_reversible',
            self::LockUnavailable => 'lock_unavailable',
            self::Execution => 'execution',
            self::UncertainOrCleanup => 'uncertain_or_cleanup',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Invalid migration definition',
            self::HistoryMismatch => 'Migration history does not match the catalog',
            self::DestructiveReviewRequired => 'Destructive migration review is required',
            self::NotReversible => 'Migration is not reversible',
            self::LockUnavailable => 'Migration lock is unavailable',
            self::Execution => 'Migration execution failed',
            self::UncertainOrCleanup => 'Migration state is uncertain or cleanup failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidDefinition => 'Construct a bounded catalog from the documented version-1 migration vocabulary.',
            self::HistoryMismatch => 'Restore exact catalog and applied-history agreement before planning again.',
            self::DestructiveReviewRequired => 'Review the declared destructive operations and explicitly allow them when intended.',
            self::NotReversible => 'Provide an explicit Revert plan or use a separately reviewed recovery procedure.',
            self::LockUnavailable => 'Wait for the current migration owner to finish before trying again.',
            self::Execution => 'Correct the migration or provider state before starting a new execution attempt.',
            self::UncertainOrCleanup => 'Retire the uncertain database state and preserve every failure for investigation.',
        };
    }

    public function context(
        ?MigrationDirection $direction = null,
        ?int $ordinal = null,
        ?string $operationKind = null,
        ?MigrationRisk $risk = null,
        ?string $lifecyclePhase = null,
        ?int $previousCount = null,
        ?int $resultingCount = null,
        ?string $profile = null,
    ): DiagnosticContext {
        $schema = new DiagnosticContextSchema(
            'Plan or execute deterministic schema migrations',
            'Migration definitions, history, policy, locking, and lifecycle must remain exact and non-disclosing',
            [
                new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('direction', 'Direction', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('ordinal', 'Ordinal', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('operation_kind', 'Operation kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('risk', 'Risk', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('lifecycle_phase', 'Lifecycle phase', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('previous_count', 'Previous count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('resulting_count', 'Resulting count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('profile', 'Profile', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            ],
        );
        $values = ['package' => 'database-migration', 'failure_category' => $this->category()];
        if (null !== $direction) {
            $values['direction'] = $direction->value;
        }
        if (null !== $ordinal) {
            $values['ordinal'] = $ordinal;
        }
        if (null !== $operationKind) {
            $values['operation_kind'] = $operationKind;
        }
        if (null !== $risk) {
            $values['risk'] = $risk->value;
        }
        if (null !== $lifecyclePhase) {
            $values['lifecycle_phase'] = $lifecyclePhase;
        }
        if (null !== $previousCount) {
            $values['previous_count'] = $previousCount;
        }
        if (null !== $resultingCount) {
            $values['resulting_count'] = $resultingCount;
        }
        if (null !== $profile) {
            $values['profile'] = $profile;
        }

        return new DiagnosticContext($schema, $values);
    }
}
