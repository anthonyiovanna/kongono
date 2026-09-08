<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for policy evidence building. */
#[Api(ApiStatus::Internal)]
enum NetworkEgressPolicyBuildDiagnostic: string
{
    case InvalidPolicyIdentity = 'KGN-EGRESS-POLICY-1510';
    case InvalidPolicySource = 'KGN-EGRESS-POLICY-1511';
    case PolicyCompatibilityFailure = 'KGN-EGRESS-POLICY-1512';
    case InvalidPolicyArtifact = 'KGN-EGRESS-POLICY-1513';
    case StalePolicyArtifact = 'KGN-EGRESS-POLICY-1514';
    case PublicationFailure = 'KGN-EGRESS-POLICY-1515';
    case PolicyBuildLimitExceeded = 'KGN-EGRESS-POLICY-1516';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('field', 'Field', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('numeric_limit', 'Numeric limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('artifact_bytes', 'Artifact bytes', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
        ]);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidPolicyIdentity => 'Network egress policy identity is invalid',
            self::InvalidPolicySource => 'Network egress policy source is invalid',
            self::PolicyCompatibilityFailure => 'Network egress policy source is incompatible',
            self::InvalidPolicyArtifact => 'Network egress policy artifact is invalid',
            self::StalePolicyArtifact => 'Network egress policy artifact is stale',
            self::PublicationFailure => 'Network egress policy artifact publication failed',
            self::PolicyBuildLimitExceeded => 'Network egress policy build limit is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidPolicyIdentity => 'Supply one bounded lowercase ASCII policy identity matching the documented grammar',
            self::InvalidPolicySource => 'Supply one complete canonical typed network egress policy source',
            self::PolicyCompatibilityFailure => 'Align origins modes budgets and declared transport capabilities',
            self::InvalidPolicyArtifact => 'Supply exact canonical version-1 network egress policy artifact bytes',
            self::StalePolicyArtifact => 'Build fresh evidence from the explicit current policy source',
            self::PublicationFailure => 'Use one canonical existing ordinary directory without a conflicting target',
            self::PolicyBuildLimitExceeded => 'Reduce the supplied artifact evidence to the documented bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidPolicyIdentity => 'Create network egress policy identity',
            self::InvalidPolicySource => 'Create network egress policy source',
            self::PolicyCompatibilityFailure => 'Validate network egress policy compatibility',
            self::InvalidPolicyArtifact => 'Validate network egress policy artifact',
            self::StalePolicyArtifact => 'Validate network egress policy artifact currentness',
            self::PublicationFailure => 'Publish network egress policy artifact',
            self::PolicyBuildLimitExceeded => 'Validate network egress policy build bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidPolicyIdentity => 'Policy identities must preserve the exact bounded lowercase ASCII grammar',
            self::InvalidPolicySource => 'Origins must be typed unique bounded and supplied in canonical order',
            self::PolicyCompatibilityFailure => 'Declared postures budgets and capabilities must be internally coherent',
            self::InvalidPolicyArtifact => 'Artifact framing structure values compatibility and digests must be canonical and exact',
            self::StalePolicyArtifact => 'Artifact bytes must equal fresh derivation from the explicit current source',
            self::PublicationFailure => 'Publication must be path-safe atomic content-addressed and no-clobber',
            self::PolicyBuildLimitExceeded => 'Canonical artifact evidence must remain within the fixed byte bound',
        };
    }
}
