<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing Phase 14G diagnostic catalog. */
#[Api(ApiStatus::Internal)]
enum ModelInvocationInspectionDiagnostic: string
{
    case InvalidInspection = 'KGN-MODEL-INSPECTION-1437';
    case BindingMismatch = 'KGN-MODEL-INSPECTION-1438';
    case CompletedRuntimeMismatch = 'KGN-MODEL-INSPECTION-1439';
    case InvalidIntelligenceEvidence = 'KGN-MODEL-INSPECTION-1440';
    case ProjectionFailure = 'KGN-MODEL-INSPECTION-1441';

    public function context(string $component): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Inspect bounded model-invocation evidence',
                'Supply exact typed evidence without disclosing raw model, provider, message, result, receipt, action, or credential content',
                [new DiagnosticFieldSchema('component', 'Component', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['component' => $component],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Model-invocation inspection request or value is invalid',
            self::BindingMismatch => 'Model-invocation profile and wiring evidence do not bind',
            self::CompletedRuntimeMismatch => 'Completed model-invocation runtime evidence does not bind',
            self::InvalidIntelligenceEvidence => 'Graph or role-addressed model-invocation evidence is invalid',
            self::ProjectionFailure => 'Model-invocation inspection projection failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Supply one exact profile, matching generated wiring, and optional detached completed runtime evidence.',
            self::BindingMismatch => 'Supply profile, source, artifact, request, and generated wiring with identical accepted bindings.',
            self::CompletedRuntimeMismatch => 'Supply retired cleanup-complete evidence attributable to the exact invocation and profile.',
            self::InvalidIntelligenceEvidence => 'Supply matching-role bounded Graph evidence and exact ordered role evidence.',
            self::ProjectionFailure => 'Supply the exact inspection and explicit neutral Observability context required by the projection.',
        };
    }
}
