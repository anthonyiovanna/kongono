<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing invocation-plan build diagnostics. */
#[Api(ApiStatus::Internal)]
enum ModelInvocationBuildDiagnostic: string
{
    case InvalidPlanIdentity = 'KGN-MODEL-BUILD-1410';
    case InvalidAdapterIdentity = 'KGN-MODEL-BUILD-1411';
    case InvalidExecutionBudget = 'KGN-MODEL-BUILD-1412';
    case InvalidSource = 'KGN-MODEL-BUILD-1413';
    case InvalidArtifact = 'KGN-MODEL-BUILD-1414';
    case StaleArtifact = 'KGN-MODEL-BUILD-1415';
    case PublicationFailed = 'KGN-MODEL-BUILD-1416';

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
            self::InvalidPlanIdentity => 'Invocation plan identity is invalid',
            self::InvalidAdapterIdentity => 'Invocation adapter identity is invalid',
            self::InvalidExecutionBudget => 'Invocation execution budget is invalid',
            self::InvalidSource => 'Invocation plan source is invalid',
            self::InvalidArtifact => 'Invocation plan artifact is invalid',
            self::StaleArtifact => 'Invocation plan artifact is stale',
            self::PublicationFailed => 'Invocation plan artifact publication failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidPlanIdentity => 'Supply one bounded canonical invocation plan identity',
            self::InvalidAdapterIdentity => 'Supply one bounded canonical application-owned adapter identity',
            self::InvalidExecutionBudget => 'Supply one finite canonical version-1 execution budget',
            self::InvalidSource => 'Supply one exact version-1 typed invocation plan source',
            self::InvalidArtifact => 'Supply exact canonical version-1 invocation plan artifact bytes',
            self::StaleArtifact => 'Build a fresh artifact from the explicit current invocation plan source',
            self::PublicationFailed => 'Use one canonical existing ordinary directory without a conflicting target',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidPlanIdentity => 'Create invocation plan identity',
            self::InvalidAdapterIdentity => 'Create invocation adapter identity',
            self::InvalidExecutionBudget => 'Create invocation execution budget',
            self::InvalidSource => 'Create invocation plan source',
            self::InvalidArtifact => 'Validate invocation plan artifact',
            self::StaleArtifact => 'Validate invocation plan artifact currentness',
            self::PublicationFailed => 'Publish invocation plan artifact',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidPlanIdentity, self::InvalidAdapterIdentity => 'Identity labels must use the exact bounded binary grammar',
            self::InvalidExecutionBudget => 'Budget limits must be finite, detached, unique, and canonically ordered',
            self::InvalidSource => 'The source must bind one exact version-1 request, adapter identity, and budget',
            self::InvalidArtifact => 'Artifact framing, structure, values, compatibility, and digests must be canonical and exact',
            self::StaleArtifact => 'Artifact bytes must equal fresh derivation from the explicit current source',
            self::PublicationFailed => 'Publication must be path-safe, atomic, content-addressed, and no-clobber',
        };
    }
}
