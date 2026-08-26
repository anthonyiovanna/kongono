<?php

declare(strict_types=1);

namespace Kongono\Persistence\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing version-1 persistence failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum PersistenceDiagnostic: string
{
    case Validation = 'KGN-PERSISTENCE-1001';
    case LifecycleState = 'KGN-PERSISTENCE-1002';
    case IdentityConflict = 'KGN-PERSISTENCE-1003';
    case OptimisticConflict = 'KGN-PERSISTENCE-1004';
    case Operation = 'KGN-PERSISTENCE-1005';
    case Cleanup = 'KGN-PERSISTENCE-1006';
    case CombinedFailure = 'KGN-PERSISTENCE-1007';
    case TerminalUncertainty = 'KGN-PERSISTENCE-1008';
    case AggregateRepositoryEvidence = 'KGN-PERSISTENCE-1009';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Use operation-scoped persistence',
                'Persistence failures must remain explicit, bounded, and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'persistence',
                'failure_category' => $this->category(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function category(): string
    {
        return match ($this) {
            self::Validation => 'validation',
            self::LifecycleState => 'lifecycle_state',
            self::IdentityConflict => 'identity_conflict',
            self::OptimisticConflict => 'optimistic_conflict',
            self::Operation => 'operation',
            self::Cleanup => 'cleanup',
            self::CombinedFailure => 'combined_failure',
            self::TerminalUncertainty => 'terminal_uncertainty',
            self::AggregateRepositoryEvidence => 'aggregate_repository_evidence',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Validation => 'Invalid persistence value',
            self::LifecycleState => 'Invalid persistence lifecycle state',
            self::IdentityConflict => 'Persistence identity conflict',
            self::OptimisticConflict => 'Persistence optimistic conflict',
            self::Operation => 'Persistence operation failed',
            self::Cleanup => 'Persistence cleanup failed',
            self::CombinedFailure => 'Persistence operation and cleanup failed',
            self::TerminalUncertainty => 'Persistence outcome is uncertain',
            self::AggregateRepositoryEvidence => 'Invalid aggregate persistence evidence',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Validation => 'Provide a value within the documented neutral persistence bounds.',
            self::LifecycleState => 'Use the context only in its documented active lifecycle state.',
            self::IdentityConflict => 'Resolve the duplicate entity identity before continuing the operation.',
            self::OptimisticConflict => 'Resolve the optimistic conflict without exposing token contents.',
            self::Operation => 'Inspect the retained cause through a protected operational boundary.',
            self::Cleanup => 'Retire uncertain context state and inspect the retained cleanup cause.',
            self::CombinedFailure => 'Preserve both failures and retire uncertain context state.',
            self::TerminalUncertainty => 'Retire the context and inspect the retained cause through a protected operational boundary.',
            self::AggregateRepositoryEvidence => 'Regenerate the aggregate repository evidence from authenticated current inputs.',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::Validation => 'correct_input',
            self::LifecycleState => 'respect_lifecycle',
            self::IdentityConflict => 'resolve_identity',
            self::OptimisticConflict => 'resolve_conflict',
            self::Operation => 'inspect_operation',
            self::Cleanup => 'retire_context',
            self::CombinedFailure => 'preserve_failures',
            self::TerminalUncertainty => 'retire_uncertain_context',
            self::AggregateRepositoryEvidence => 'regenerate_aggregate_evidence',
        };
    }
}
