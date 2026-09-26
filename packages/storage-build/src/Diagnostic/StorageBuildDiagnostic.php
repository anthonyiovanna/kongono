<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed bounded diagnostic vocabulary for storage-profile builds. */
#[Api(ApiStatus::Internal)]
enum StorageBuildDiagnostic: string
{
    case InvalidProfile = 'KGN-STORAGE-BUILD-2201';
    case InvalidArtifact = 'KGN-STORAGE-BUILD-2202';
    case StaleArtifact = 'KGN-STORAGE-BUILD-2203';
    case PublicationFailed = 'KGN-STORAGE-BUILD-2204';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('reason', 'Reason', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('profile_count', 'Profile count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('profile_position', 'Profile position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('artifact_byte_count', 'Artifact byte count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('cleanup_status', 'Cleanup status', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('identity', 'Identity', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
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
            self::InvalidProfile => 'Storage profile set is invalid',
            self::InvalidArtifact => 'Storage profile artifact is invalid',
            self::StaleArtifact => 'Storage profile artifact is stale',
            self::PublicationFailed => 'Storage profile publication failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidProfile => 'Supply one complete bounded Version 1 profile set with unique names and namespaces',
            self::InvalidArtifact => 'Supply exact canonical Version 1 storage-profile artifact bytes',
            self::StaleArtifact => 'Rebuild from the explicitly supplied complete current profile set',
            self::PublicationFailed => 'Use one explicit safe root and an absent or byte-identical content-addressed target',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidProfile => 'Validate storage profile set',
            self::InvalidArtifact => 'Validate storage profile artifact',
            self::StaleArtifact => 'Validate storage profile currentness',
            self::PublicationFailed => 'Publish storage profile artifact',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidProfile => 'Profile declarations are explicit, finite, unique, detached, and ordinally canonical',
            self::InvalidArtifact => 'Artifact framing, schema, ordering, identities, counts, and declarations are exact',
            self::StaleArtifact => 'The complete current profile set and rebuilt artifact agree byte for byte',
            self::PublicationFailed => 'Publication is contained, atomic, no-clobber, exact-idempotent, and cleanup truthful',
        };
    }
}
