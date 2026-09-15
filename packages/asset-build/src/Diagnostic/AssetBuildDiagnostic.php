<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed bounded diagnostic vocabulary for native asset builds. */
#[Api(ApiStatus::Internal)]
enum AssetBuildDiagnostic: string
{
    case InvalidSource = 'KGN-ASSET-BUILD-1911';
    case InvalidGraph = 'KGN-ASSET-BUILD-1912';
    case InvalidBuildOutput = 'KGN-ASSET-BUILD-1913';
    case InvalidArtifact = 'KGN-ASSET-BUILD-1914';
    case StaleArtifact = 'KGN-ASSET-BUILD-1915';
    case PublicationFailed = 'KGN-ASSET-BUILD-1916';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('profile', 'Profile', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('dependency_kind', 'Dependency kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('depth', 'Depth', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('cleanup_status', 'Cleanup status', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('logical_identity', 'Logical identity', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
            new DiagnosticFieldSchema('sha256', 'SHA-256', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
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
            self::InvalidSource => 'Asset source set is invalid',
            self::InvalidGraph => 'Asset dependency graph is invalid',
            self::InvalidBuildOutput => 'Native asset build output is invalid',
            self::InvalidArtifact => 'Asset build artifact is invalid',
            self::StaleArtifact => 'Asset build artifact is stale',
            self::PublicationFailed => 'Asset build publication failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidSource => 'Supply one complete bounded source for every declared logical asset',
            self::InvalidGraph => 'Supply an acyclic reachable dependency graph within the documented depth bound',
            self::InvalidBuildOutput => 'Use the fixed native-copy profile and byte-identical bounded outputs',
            self::InvalidArtifact => 'Supply exact canonical artifact bytes and the complete matching output map',
            self::StaleArtifact => 'Rebuild from the explicitly supplied complete current source set',
            self::PublicationFailed => 'Use one explicit safe writable directory and an absent or byte-identical target',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidSource => 'Validate asset source set',
            self::InvalidGraph => 'Build asset dependency graph',
            self::InvalidBuildOutput => 'Build native asset output',
            self::InvalidArtifact => 'Validate asset build artifact',
            self::StaleArtifact => 'Validate asset build currentness',
            self::PublicationFailed => 'Publish asset build result',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidSource => 'Source paths, identities, bytes, coverage, and collection bounds must be exact',
            self::InvalidGraph => 'All declarations must be reachable and every dependency kind must remain acyclic and bounded',
            self::InvalidBuildOutput => 'Native outputs must preserve exact bytes, metadata, content identities, and paths',
            self::InvalidArtifact => 'Artifact framing, schema, ordering, identities, and output evidence must be exact',
            self::StaleArtifact => 'The complete current source set and rebuilt result must agree byte for byte',
            self::PublicationFailed => 'Publication must be contained, atomic, no-clobber, and exact-idempotent',
        };
    }
}
