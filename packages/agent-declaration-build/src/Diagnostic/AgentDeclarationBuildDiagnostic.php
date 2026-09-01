<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns fixed non-disclosing declaration-build failure metadata. */
#[Api(ApiStatus::Internal)]
enum AgentDeclarationBuildDiagnostic: string
{
    case InvalidDeclaration = 'KGN-AGENT-DECLARATION-1310';
    case InvalidArtifact = 'KGN-AGENT-DECLARATION-1311';
    case StaleArtifact = 'KGN-AGENT-DECLARATION-1312';
    case PublicationFailed = 'KGN-AGENT-DECLARATION-1313';
    case BuildLimitExceeded = 'KGN-AGENT-DECLARATION-1314';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = [new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)];
        if (self::InvalidDeclaration === $this || self::InvalidArtifact === $this || self::BuildLimitExceeded === $this) {
            $fields[] = new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false);
            $fields[] = new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false);
            $fields[] = new DiagnosticFieldSchema('artifact_bytes', 'Artifact bytes', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false);
        }

        return new DiagnosticContextSchema($this->operation(), $this->invariant(), $fields);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidDeclaration => 'Agent declaration is invalid',
            self::InvalidArtifact => 'Agent declaration artifact is invalid',
            self::StaleArtifact => 'Agent declaration artifact is stale',
            self::PublicationFailed => 'Agent declaration artifact publication failed',
            self::BuildLimitExceeded => 'Agent declaration artifact limit is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidDeclaration => 'Supply one exact typed canonical Agent declaration',
            self::InvalidArtifact => 'Supply exact canonical version-1 Agent declaration artifact bytes',
            self::StaleArtifact => 'Build a fresh artifact from the explicit current declaration',
            self::PublicationFailed => 'Use one canonical existing ordinary directory without a conflicting target',
            self::BuildLimitExceeded => 'Reduce the declaration lists or artifact within the fixed bounds',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidDeclaration => 'Create Agent declaration',
            self::InvalidArtifact => 'Validate Agent declaration artifact',
            self::StaleArtifact => 'Validate Agent declaration artifact currentness',
            self::PublicationFailed => 'Publish Agent declaration artifact',
            self::BuildLimitExceeded => 'Enforce Agent declaration artifact bounds',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidDeclaration => 'Declaration values must be typed, unique, bounded, and in strict binary order',
            self::InvalidArtifact => 'Artifact bytes, structure, reconstructed declaration, and digests must be canonical and exact',
            self::StaleArtifact => 'Artifact bytes must equal fresh derivation from the explicit current declaration',
            self::PublicationFailed => 'Publication must be path-safe, atomic, content-addressed, and no-clobber',
            self::BuildLimitExceeded => 'Declaration artifact work must remain within fixed documented limits',
        };
    }
}
