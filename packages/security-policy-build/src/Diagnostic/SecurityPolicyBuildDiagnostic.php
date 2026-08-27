<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing policy-build diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum SecurityPolicyBuildDiagnostic: string
{
    case InvalidApplicationRoot = 'KGN-SECURITY-POLICY-BUILD-1001';
    case UnsafeSource = 'KGN-SECURITY-POLICY-BUILD-1002';
    case InvalidSource = 'KGN-SECURITY-POLICY-BUILD-1003';
    case InvalidArtifact = 'KGN-SECURITY-POLICY-BUILD-1004';
    case StaleArtifact = 'KGN-SECURITY-POLICY-BUILD-1005';
    case PublicationFailed = 'KGN-SECURITY-POLICY-BUILD-1006';
    case WorkflowFailed = 'KGN-SECURITY-POLICY-BUILD-1007';

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
            'package' => 'security_policy_build',
            'failure_category' => $this->category(),
            'remediation' => $this->remediationLabel(),
        ]);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'Invalid security policy application root',
            self::UnsafeSource => 'Unsafe security policy source',
            self::InvalidSource => 'Invalid security policy source',
            self::InvalidArtifact => 'Invalid security policy artifact',
            self::StaleArtifact => 'Stale security policy artifact',
            self::PublicationFailed => 'Security policy publication failed',
            self::WorkflowFailed => 'Security policy workflow failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'Supply one exact ordinary application root',
            self::UnsafeSource => 'Restore the exact contained ordinary policy source',
            self::InvalidSource => 'Correct the closed version-1 policy identity source',
            self::InvalidArtifact => 'Regenerate the canonical policy artifact',
            self::StaleArtifact => 'Build and check a new artifact from current source bytes',
            self::PublicationFailed => 'Resolve the contained output conflict and retry explicitly',
            self::WorkflowFailed => 'Correct the failed explicit policy workflow stage',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'Resolve security policy application root',
            self::UnsafeSource => 'Admit security policy file state',
            self::InvalidSource => 'Compile security policy identity source',
            self::InvalidArtifact => 'Validate security policy artifact structure',
            self::StaleArtifact => 'Validate exact current security policy evidence',
            self::PublicationFailed => 'Publish security policy artifact',
            self::WorkflowFailed => 'Inspect, build, or check security policy evidence',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidApplicationRoot => 'The application root must be exact, ordinary, and free of aliases',
            self::UnsafeSource => 'Required policy files must be exact, contained, ordinary, and stable',
            self::InvalidSource => 'The policy source must be closed, bounded, rule-free version-1 identity data',
            self::InvalidArtifact => 'The artifact must be canonical and internally identity-consistent',
            self::StaleArtifact => 'Loaded evidence must equal a complete current rebuild',
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
