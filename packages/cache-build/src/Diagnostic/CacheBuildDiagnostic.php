<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed bounded diagnostic vocabulary for cache-profile builds. */
#[Api(ApiStatus::Internal)]
enum CacheBuildDiagnostic: string
{
    case InvalidProfile = 'KGN-CACHE-BUILD-2011';
    case InvalidArtifact = 'KGN-CACHE-BUILD-2012';
    case StaleArtifact = 'KGN-CACHE-BUILD-2013';
    case PublicationFailed = 'KGN-CACHE-BUILD-2014';

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
            self::InvalidProfile => 'Cache profile set is invalid',
            self::InvalidArtifact => 'Cache profile artifact is invalid',
            self::StaleArtifact => 'Cache profile artifact is stale',
            self::PublicationFailed => 'Cache profile publication failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidProfile => 'Supply one complete bounded Version 1 profile set with unique names and namespaces',
            self::InvalidArtifact => 'Supply exact canonical Version 1 cache-profile artifact bytes',
            self::StaleArtifact => 'Rebuild from the explicitly supplied complete current profile set',
            self::PublicationFailed => 'Use one explicit safe root and an absent or byte-identical content-addressed target',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidProfile => 'Validate cache profile set',
            self::InvalidArtifact => 'Validate cache profile artifact',
            self::StaleArtifact => 'Validate cache profile currentness',
            self::PublicationFailed => 'Publish cache profile artifact',
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
