<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing extension artifact diagnostic contract. */
#[Api(ApiStatus::Internal)]
enum ExtensionBuildDiagnostic: string
{
    case InvalidBuildInput = 'KGN-EXTENSION-1208';
    case InvalidArtifact = 'KGN-EXTENSION-1209';
    case StaleArtifact = 'KGN-EXTENSION-1210';
    case PublicationFailed = 'KGN-EXTENSION-1211';
    case BuildLimitExceeded = 'KGN-EXTENSION-1212';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('kind', 'Kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
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
            self::InvalidBuildInput => 'Extension declaration build input is invalid',
            self::InvalidArtifact => 'Extension declaration artifact is invalid',
            self::StaleArtifact => 'Extension declaration artifact is stale',
            self::PublicationFailed => 'Extension declaration artifact publication failed',
            self::BuildLimitExceeded => 'Extension declaration artifact limit is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidBuildInput => 'Supply one exact typed canonical declaration list',
            self::InvalidArtifact => 'Supply exact canonical version-1 declaration artifact bytes',
            self::StaleArtifact => 'Build a fresh artifact from the caller-supplied current declarations',
            self::PublicationFailed => 'Use one canonical existing ordinary directory without a conflicting target',
            self::BuildLimitExceeded => 'Reduce the declaration set or artifact within the documented fixed bounds',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidBuildInput => 'Build extension declaration artifact',
            self::InvalidArtifact => 'Validate extension declaration artifact',
            self::StaleArtifact => 'Validate extension declaration artifact currentness',
            self::PublicationFailed => 'Publish extension declaration artifact',
            self::BuildLimitExceeded => 'Enforce extension declaration artifact bounds',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidBuildInput => 'Declarations must be typed, unique, bounded, and in strict binary identity order',
            self::InvalidArtifact => 'Artifact bytes, structure, reconstructed declarations, and identities must be canonical and exact',
            self::StaleArtifact => 'Artifact bytes must equal fresh derivation from caller-supplied current declarations',
            self::PublicationFailed => 'Publication must be path-safe, atomic, content-addressed, and no-clobber',
            self::BuildLimitExceeded => 'Declaration artifact work must remain within fixed documented limits',
        };
    }
}
