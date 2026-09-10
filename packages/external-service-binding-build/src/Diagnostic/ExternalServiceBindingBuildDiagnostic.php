<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for secret-free binding builds. */
#[Api(ApiStatus::Internal)]
enum ExternalServiceBindingBuildDiagnostic: string
{
    case InvalidSource = 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1609';
    case InvalidArtifact = 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1610';
    case StaleArtifact = 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1611';
    case ArtifactLimitExceeded = 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1612';
    case ArtifactPublicationFailed = 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1613';
    case InvalidGeneratedPhp = 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1614';
    case StaleGeneratedPhp = 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1615';
    case GeneratedPhpLimitExceeded = 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1616';
    case GeneratedPhpPublicationFailed = 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1617';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('field', 'Field', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
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
            self::InvalidSource => 'External-service binding source is invalid',
            self::InvalidArtifact => 'External-service binding artifact is invalid',
            self::StaleArtifact => 'External-service binding artifact is stale',
            self::ArtifactLimitExceeded => 'External-service binding artifact limit is exceeded',
            self::ArtifactPublicationFailed => 'External-service binding artifact publication failed',
            self::InvalidGeneratedPhp => 'Generated external-service binding PHP is invalid',
            self::StaleGeneratedPhp => 'Generated external-service binding PHP is stale',
            self::GeneratedPhpLimitExceeded => 'Generated external-service binding PHP limit is exceeded',
            self::GeneratedPhpPublicationFailed => 'Generated external-service binding PHP publication failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidSource => 'Supply one bounded canonical typed secret-free binding source',
            self::InvalidArtifact => 'Supply exact canonical version-1 external-service binding artifact bytes',
            self::StaleArtifact => 'Build fresh artifact evidence from the explicit current source',
            self::ArtifactLimitExceeded => 'Reduce binding evidence to the documented artifact byte bound',
            self::ArtifactPublicationFailed => 'Use one canonical existing ordinary directory without a conflicting artifact target',
            self::InvalidGeneratedPhp => 'Supply exact deterministic version-1 generated binding PHP bytes',
            self::StaleGeneratedPhp => 'Generate fresh PHP evidence from the explicit current source and artifact',
            self::GeneratedPhpLimitExceeded => 'Reduce binding evidence to the documented generated PHP byte bound',
            self::GeneratedPhpPublicationFailed => 'Use one canonical existing ordinary directory without a conflicting generated target',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidSource => 'Validate external-service binding source',
            self::InvalidArtifact => 'Validate external-service binding artifact',
            self::StaleArtifact => 'Validate external-service binding artifact currentness',
            self::ArtifactLimitExceeded => 'Validate external-service binding artifact size',
            self::ArtifactPublicationFailed => 'Publish external-service binding artifact',
            self::InvalidGeneratedPhp => 'Validate generated external-service binding PHP',
            self::StaleGeneratedPhp => 'Validate generated external-service binding PHP currentness',
            self::GeneratedPhpLimitExceeded => 'Validate generated external-service binding PHP size',
            self::GeneratedPhpPublicationFailed => 'Publish generated external-service binding PHP',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidSource => 'Binding source identity entries ordering and uniqueness must be canonical and typed',
            self::InvalidArtifact => 'Artifact framing schema values provenance and digests must be canonical and exact',
            self::StaleArtifact => 'Artifact bytes must equal fresh derivation from the explicit current source',
            self::ArtifactLimitExceeded => 'Canonical artifact bytes must remain within the fixed bound',
            self::ArtifactPublicationFailed => 'Artifact publication must be path-safe atomic content-addressed and no-clobber',
            self::InvalidGeneratedPhp => 'Generated metadata values template provenance and bytes must be canonical and exact',
            self::StaleGeneratedPhp => 'Generated bytes must equal fresh derivation from the explicit current source and artifact',
            self::GeneratedPhpLimitExceeded => 'Generated PHP bytes must remain within the fixed bound',
            self::GeneratedPhpPublicationFailed => 'Generated PHP publication must be path-safe atomic content-addressed and no-clobber',
        };
    }
}
