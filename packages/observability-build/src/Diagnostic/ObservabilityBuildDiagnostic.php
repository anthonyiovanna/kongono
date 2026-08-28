<?php

declare(strict_types=1);

namespace Kongono\ObservabilityBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing observability-build diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum ObservabilityBuildDiagnostic: string
{
    case InvalidApplicationRoot = 'KGN-OBSERVABILITY-BUILD-1001';
    case UnsafeSource = 'KGN-OBSERVABILITY-BUILD-1002';
    case InvalidSource = 'KGN-OBSERVABILITY-BUILD-1003';
    case InvalidArtifact = 'KGN-OBSERVABILITY-BUILD-1004';
    case StaleArtifact = 'KGN-OBSERVABILITY-BUILD-1005';
    case PublicationFailed = 'KGN-OBSERVABILITY-BUILD-1006';
    case WorkflowFailed = 'KGN-OBSERVABILITY-BUILD-1007';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->invariant(),
            [
                new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
        ), [
            'package' => 'observability_build',
            'failure_category' => $this->category(),
            'remediation' => $this->remediationLabel(),
        ]);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'Invalid observability application root',
            self::UnsafeSource => 'Unsafe observability configuration file state',
            self::InvalidSource => 'Invalid observability configuration source',
            self::InvalidArtifact => 'Invalid observability configuration artifact',
            self::StaleArtifact => 'Stale observability configuration artifact',
            self::PublicationFailed => 'Observability configuration publication failed',
            self::WorkflowFailed => 'Observability configuration workflow failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'Supply one exact ordinary application root',
            self::UnsafeSource => 'Restore the exact contained ordinary observability file',
            self::InvalidSource => 'Correct the closed version-1 observability source',
            self::InvalidArtifact => 'Regenerate the canonical observability artifact',
            self::StaleArtifact => 'Build and check a new artifact from current source bytes',
            self::PublicationFailed => 'Resolve the contained output conflict and retry explicitly',
            self::WorkflowFailed => 'Correct the failed explicit observability workflow stage',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'Resolve observability application root',
            self::UnsafeSource => 'Admit observability file state',
            self::InvalidSource => 'Compile observability configuration source',
            self::InvalidArtifact => 'Validate observability artifact structure',
            self::StaleArtifact => 'Validate exact current observability evidence',
            self::PublicationFailed => 'Publish observability configuration artifact',
            self::WorkflowFailed => 'Inspect, build, or check observability evidence',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'The application root must be exact, ordinary, and free of aliases',
            self::UnsafeSource => 'Required files must be exact, contained, ordinary, and stable',
            self::InvalidSource => 'The source must be closed, bounded, provider-free version-1 configuration',
            self::InvalidArtifact => 'The artifact must be canonical and internally identity-consistent',
            self::StaleArtifact => 'Loaded configuration must equal a complete current rebuild',
            self::PublicationFailed => 'Publication must be atomic, content-addressed, and no-clobber',
            self::WorkflowFailed => 'Every workflow stage must preserve exact contained currentness',
        };
    }

    private function category(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'application_root',
            self::UnsafeSource => 'unsafe_file_state',
            self::InvalidSource => 'source_validation',
            self::InvalidArtifact => 'artifact_validation',
            self::StaleArtifact => 'currentness',
            self::PublicationFailed => 'publication',
            self::WorkflowFailed => 'workflow',
        };
    }

    private function remediationLabel(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'supply_exact_root',
            self::UnsafeSource => 'restore_ordinary_file',
            self::InvalidSource => 'correct_closed_source',
            self::InvalidArtifact => 'regenerate_artifact',
            self::StaleArtifact => 'rebuild_current_evidence',
            self::PublicationFailed => 'resolve_no_clobber_conflict',
            self::WorkflowFailed => 'correct_workflow_precondition',
        };
    }
}
