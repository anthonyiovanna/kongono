<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing Phase 19G diagnostic catalog. */
#[Api(ApiStatus::Internal)]
enum AssetInspectionDiagnostic: string
{
    case InvalidInspection = 'KGN-ASSET-INSPECTION-1950';
    case ArtifactBindingFailure = 'KGN-ASSET-INSPECTION-1951';
    case InvalidIntelligenceEvidence = 'KGN-ASSET-INSPECTION-1952';
    case ProjectionFailure = 'KGN-ASSET-INSPECTION-1953';
    case VerificationFailure = 'KGN-ASSET-INSPECTION-1954';

    public function context(string $component): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Inspect bounded asset evidence',
                'Supply exact typed in-memory evidence without disclosing asset identities, paths, URLs, bytes, metadata, or foreign messages',
                [new DiagnosticFieldSchema('component', 'Component', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['component' => $component],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Asset inspection request or evidence is invalid',
            self::ArtifactBindingFailure => 'Asset artifact or current-result binding failed',
            self::InvalidIntelligenceEvidence => 'Asset role, Graph, or intelligence evidence is invalid',
            self::ProjectionFailure => 'Asset inspection projection failed',
            self::VerificationFailure => 'Independent asset inspection verification failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Supply one exact typed current asset-application result and package-owned evidence values.',
            self::ArtifactBindingFailure => 'Supply one coherent structurally valid current result and resolution artifact.',
            self::InvalidIntelligenceEvidence => 'Supply exact ordered role evidence and optional matching-role bounded Graph aggregates.',
            self::ProjectionFailure => 'Supply the validated inspection and an explicit neutral Observability context.',
            self::VerificationFailure => 'Replace the candidate with independently reconstructed untampered evidence; no repair is performed.',
        };
    }
}
