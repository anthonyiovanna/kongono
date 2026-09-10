<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed nondisclosing Phase 16G diagnostic catalog. */
#[Api(ApiStatus::Internal)]
enum ExternalServiceInspectionDiagnostic: string
{
    case InvalidInspection = 'KGN-EXTERNAL-SERVICE-INSPECTION-1628';
    case BindingMismatch = 'KGN-EXTERNAL-SERVICE-INSPECTION-1629';
    case ResultEvidenceMismatch = 'KGN-EXTERNAL-SERVICE-INSPECTION-1630';
    case InvalidIntelligenceEvidence = 'KGN-EXTERNAL-SERVICE-INSPECTION-1631';
    case ProjectionFailure = 'KGN-EXTERNAL-SERVICE-INSPECTION-1632';

    public function context(string $component): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Inspect bounded external-service evidence',
                'Supply exact typed evidence without disclosing identities, endpoints, credentials, requests, responses, artifacts, or foreign content',
                [new DiagnosticFieldSchema('component', 'Component', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['component' => $component],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInspection => 'External-service inspection request or value is invalid',
            self::BindingMismatch => 'External-service profile, binding, artifact, and policy evidence do not bind',
            self::ResultEvidenceMismatch => 'External-service, credential, and egress result evidence do not bind',
            self::InvalidIntelligenceEvidence => 'Graph or role-addressed external-service evidence is invalid',
            self::ProjectionFailure => 'External-service inspection projection failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Supply one exact Phase 16F profile and an optional detached completed result.',
            self::BindingMismatch => 'Supply one exact current binding source, artifact, selected binding, and nested network profile.',
            self::ResultEvidenceMismatch => 'Supply coherent selected-binding, credential, operation, receipt, and terminal egress evidence.',
            self::InvalidIntelligenceEvidence => 'Supply matching-role bounded Graph evidence and exact ordered role evidence.',
            self::ProjectionFailure => 'Supply the exact inspection and explicit neutral Observability context required by the projection.',
        };
    }
}
