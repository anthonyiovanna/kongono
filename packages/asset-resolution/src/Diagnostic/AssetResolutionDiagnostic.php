<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the nine closed nondisclosing asset-resolution diagnostics. */
#[Api(ApiStatus::Internal)]
enum AssetResolutionDiagnostic: string
{
    case InvalidContract = 'KGN-ASSET-RESOLUTION-1930';
    case InvalidArtifact = 'KGN-ASSET-RESOLUTION-1931';
    case StaleIdentity = 'KGN-ASSET-RESOLUTION-1932';
    case MissingIdentity = 'KGN-ASSET-RESOLUTION-1933';
    case InvalidPublicUrlPolicy = 'KGN-ASSET-RESOLUTION-1934';
    case UnsatisfiedIntegrityPolicy = 'KGN-ASSET-RESOLUTION-1935';
    case InvalidPreloadRelation = 'KGN-ASSET-RESOLUTION-1936';
    case InvalidHttpMetadataRelation = 'KGN-ASSET-RESOLUTION-1937';
    case ResolutionRelationFailure = 'KGN-ASSET-RESOLUTION-1938';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('producer_kind', 'Producer kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false),
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
            self::InvalidContract => 'Asset resolution contract is invalid',
            self::InvalidArtifact => 'Asset resolution artifact is invalid',
            self::StaleIdentity => 'Asset resolution artifact identity is stale',
            self::MissingIdentity => 'Logical asset identity is not present',
            self::InvalidPublicUrlPolicy => 'Public asset URL policy is invalid',
            self::UnsatisfiedIntegrityPolicy => 'Browser integrity policy is unsatisfied',
            self::InvalidPreloadRelation => 'Asset preload relation is invalid',
            self::InvalidHttpMetadataRelation => 'Asset HTTP metadata relation is invalid',
            self::ResolutionRelationFailure => 'Asset resolution relation failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidContract => 'Supply the documented Version 1 resolution contract',
            self::InvalidArtifact => 'Rebuild and completely validate one canonical resolution artifact',
            self::StaleIdentity => 'Supply the exact expected identity for the selected current artifact',
            self::MissingIdentity => 'Declare and build the requested logical asset explicitly',
            self::InvalidPublicUrlPolicy => 'Supply one canonical explicit root-relative or HTTP or HTTPS base URL',
            self::UnsatisfiedIntegrityPolicy => 'Supply consistent validated SHA-384 artifact evidence or select omit',
            self::InvalidPreloadRelation => 'Supply a closed preload hint compatible with the asset kind',
            self::InvalidHttpMetadataRelation => 'Supply consistent bounded content type length identity and cache policy',
            self::ResolutionRelationFailure => 'Supply complete unique deterministic resolution relations',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidContract => 'Validate asset resolution contract',
            self::InvalidArtifact => 'Validate asset resolution artifact',
            self::StaleIdentity => 'Check exact-current artifact identity',
            self::MissingIdentity => 'Look up logical asset identity',
            self::InvalidPublicUrlPolicy => 'Validate public asset URL policy',
            self::UnsatisfiedIntegrityPolicy => 'Apply browser integrity policy',
            self::InvalidPreloadRelation => 'Apply preload policy',
            self::InvalidHttpMetadataRelation => 'Create asset HTTP metadata',
            self::ResolutionRelationFailure => 'Resolve asset relations',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidContract => 'Only the closed Version 1 contract is accepted',
            self::InvalidArtifact => 'Artifact structure identities metadata bounds and relations must agree',
            self::StaleIdentity => 'Expected and supplied exact artifact identities must match',
            self::MissingIdentity => 'Resolution never aliases scans guesses or falls back',
            self::InvalidPublicUrlPolicy => 'Public URLs require explicit canonical application-owned policy',
            self::UnsatisfiedIntegrityPolicy => 'Required browser integrity derives only from validated SHA-384 evidence',
            self::InvalidPreloadRelation => 'Preload metadata remains closed inert and kind-compatible',
            self::InvalidHttpMetadataRelation => 'HTTP metadata remains typed bounded and internally consistent',
            self::ResolutionRelationFailure => 'Resolution remains complete unique deterministic and operation-local',
        };
    }
}
