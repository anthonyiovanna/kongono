<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing Phase 20G diagnostic catalog. */
#[Api(ApiStatus::Internal)]
enum CacheInspectionDiagnostic: string
{
    case InvalidInspection = 'KGN-CACHE-INSPECTION-2051';
    case ArtifactBindingFailure = 'KGN-CACHE-INSPECTION-2052';
    case InvalidIntelligenceEvidence = 'KGN-CACHE-INSPECTION-2053';
    case ProjectionFailure = 'KGN-CACHE-INSPECTION-2054';
    case VerificationFailure = 'KGN-CACHE-INSPECTION-2055';

    public function context(string $component): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Inspect bounded cache evidence',
                'Supply exact typed in-memory evidence without disclosing cache identities, keys, values, bytes, provider data, or foreign messages',
                [new DiagnosticFieldSchema('component', 'Component', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['component' => $component],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Cache inspection request or evidence is invalid',
            self::ArtifactBindingFailure => 'Cache profile artifact or current-build binding failed',
            self::InvalidIntelligenceEvidence => 'Detached runtime, role, Graph, or intelligence evidence is invalid',
            self::ProjectionFailure => 'Cache inspection projection failed',
            self::VerificationFailure => 'Independent cache inspection verification failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Supply one exact typed current cache-application build and package-owned bounded evidence values.',
            self::ArtifactBindingFailure => 'Supply one coherent structurally valid current profile set and artifact.',
            self::InvalidIntelligenceEvidence => 'Supply exact bounded detached runtime evidence, ordered role evidence, and optional matching-role Graph aggregates.',
            self::ProjectionFailure => 'Supply the validated inspection and an explicit neutral Observability context.',
            self::VerificationFailure => 'Replace the candidate with independently reconstructed untampered evidence; no repair is performed.',
        };
    }
}
