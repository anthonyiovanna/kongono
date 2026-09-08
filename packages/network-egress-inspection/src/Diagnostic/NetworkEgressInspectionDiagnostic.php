<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed nondisclosing Phase 15G diagnostic catalog. */
#[Api(ApiStatus::Internal)]
enum NetworkEgressInspectionDiagnostic: string
{
    case InvalidInspection = 'KGN-NET-1549';
    case BindingMismatch = 'KGN-NET-1550';
    case RuntimeEvidenceMismatch = 'KGN-NET-1551';
    case InvalidIntelligenceEvidence = 'KGN-NET-1552';
    case ProjectionFailure = 'KGN-NET-1553';

    public function context(string $component): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Inspect bounded network-egress evidence',
                'Supply exact typed evidence without disclosing raw policy, origin, address, request, response, receipt, credential, or generated content',
                [new DiagnosticFieldSchema('component', 'Component', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['component' => $component],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Network-egress inspection request or value is invalid',
            self::BindingMismatch => 'Network-egress profile, policy, and wiring evidence do not bind',
            self::RuntimeEvidenceMismatch => 'Network-egress operation and runtime evidence do not bind',
            self::InvalidIntelligenceEvidence => 'Graph or role-addressed network-egress evidence is invalid',
            self::ProjectionFailure => 'Network-egress inspection projection failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Supply one exact profile and wiring pair with either both optional runtime values or neither.',
            self::BindingMismatch => 'Supply profile, source, artifact, capability, and generated wiring with identical accepted bindings.',
            self::RuntimeEvidenceMismatch => 'Supply one exact policy-bound operation with its coherent completed detached runtime result.',
            self::InvalidIntelligenceEvidence => 'Supply matching-role bounded Graph evidence and exact ordered role evidence.',
            self::ProjectionFailure => 'Supply the exact inspection and explicit neutral Observability context required by the projection.',
        };
    }
}
