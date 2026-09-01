<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing Phase 13G diagnostic catalog. */
#[Api(ApiStatus::Internal)]
enum AgentInspectionDiagnostic: string
{
    case InvalidInspection = 'KGN-AGENT-INSPECTION-1346';
    case BindingMismatch = 'KGN-AGENT-INSPECTION-1347';
    case InvalidRuntimeEvidence = 'KGN-AGENT-INSPECTION-1348';
    case InvalidIntelligenceEvidence = 'KGN-AGENT-INSPECTION-1349';
    case InvalidProjection = 'KGN-AGENT-INSPECTION-1350';

    public function context(string $component): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Inspect bounded Agent Principal evidence',
                'Supply exact typed evidence without disclosing raw principal, capability, proposal, result, or provider content',
                [new DiagnosticFieldSchema('component', 'Component', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['component' => $component],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Agent Principal inspection request or value is invalid',
            self::BindingMismatch => 'Agent Principal inspection evidence does not bind',
            self::InvalidRuntimeEvidence => 'Completed Agent runtime evidence is invalid',
            self::InvalidIntelligenceEvidence => 'Graph or role-addressed evidence is invalid',
            self::InvalidProjection => 'Agent inspection projection is invalid',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Supply one exact profile, matching generated wiring, and an optional completed result.',
            self::BindingMismatch => 'Supply profile, declaration, plan, and wiring evidence with identical accepted bindings.',
            self::InvalidRuntimeEvidence => 'Supply one bounded completed result attributable to the exact profile principal.',
            self::InvalidIntelligenceEvidence => 'Supply matching-role bounded Graph evidence and exact ordered role evidence.',
            self::InvalidProjection => 'Supply the exact inspection and explicit Observability context required by the projection.',
        };
    }
}
