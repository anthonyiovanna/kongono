<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing Phase 17G diagnostic catalog. */
#[Api(ApiStatus::Internal)]
enum OpenAiResponsesInspectionDiagnostic: string
{
    case InvalidInspection = 'KGN-OPENAI-RESPONSES-1724';
    case BindingMismatch = 'KGN-OPENAI-RESPONSES-1725';
    case CompletedRuntimeMismatch = 'KGN-OPENAI-RESPONSES-1726';
    case InvalidIntelligenceEvidence = 'KGN-OPENAI-RESPONSES-1727';
    case ProjectionFailure = 'KGN-OPENAI-RESPONSES-1728';

    public function context(string $component): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Inspect bounded OpenAI Responses evidence',
                'Supply exact typed evidence without disclosing raw model, provider, message, result, receipt, action, or credential content',
                [new DiagnosticFieldSchema('component', 'Component', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['component' => $component],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInspection => 'OpenAI Responses inspection request or value is invalid',
            self::BindingMismatch => 'OpenAI Responses profile and component evidence do not bind',
            self::CompletedRuntimeMismatch => 'Optional OpenAI Responses runtime evidence does not bind',
            self::InvalidIntelligenceEvidence => 'Graph or role-addressed OpenAI Responses evidence is invalid',
            self::ProjectionFailure => 'OpenAI Responses inspection projection failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInspection => 'Supply one exact profile and optional detached terminal runtime evidence.',
            self::BindingMismatch => 'Supply provider, model-invocation, and external-service evidence with identical accepted bindings.',
            self::CompletedRuntimeMismatch => 'Supply retired cleanup-complete evidence attributable to the exact invocation and profile.',
            self::InvalidIntelligenceEvidence => 'Supply matching-role bounded Graph evidence and exact ordered role evidence.',
            self::ProjectionFailure => 'Supply the exact inspection and explicit neutral Observability context required by the projection.',
        };
    }
}
