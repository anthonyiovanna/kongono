<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing Phase 18G diagnostic catalog. */
#[Api(ApiStatus::Internal)]
enum ViewInspectionDiagnostic: string
{
    case InvalidInspection = 'KGN-VIEW-INSPECTION-1860';
    case ArtifactEvidenceMismatch = 'KGN-VIEW-INSPECTION-1861';
    case ProfileBindingMismatch = 'KGN-VIEW-INSPECTION-1862';
    case InvalidIntelligenceEvidence = 'KGN-VIEW-INSPECTION-1863';
    case ProjectionFailure = 'KGN-VIEW-INSPECTION-1864';

    public function context(string $component): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Inspect bounded compiled-view evidence',
                'Supply exact typed in-memory evidence without disclosing paths, source, generated, rendered, HTTP, or caller content',
                [new DiagnosticFieldSchema('component', 'Component', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['component' => $component],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Compiled-view inspection request or value is invalid',
            self::ArtifactEvidenceMismatch => 'Compiled-view artifact evidence does not match structural validation',
            self::ProfileBindingMismatch => 'Compiled-view profile and artifact bindings do not match',
            self::InvalidIntelligenceEvidence => 'Role, Graph, or compiled-view intelligence evidence is invalid',
            self::ProjectionFailure => 'Compiled-view inspection projection failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Supply one exact typed view-application profile and package-owned evidence values.',
            self::ArtifactEvidenceMismatch => 'Supply metadata and generated PHP bytes that independently validate to the supplied artifact.',
            self::ProfileBindingMismatch => 'Supply one coherent exact-current profile, current build, and artifact binding.',
            self::InvalidIntelligenceEvidence => 'Supply matching-role bounded Graph evidence and exact ordered intelligence evidence.',
            self::ProjectionFailure => 'Supply the exact inspection and explicit neutral Observability context required by the projection.',
        };
    }
}
