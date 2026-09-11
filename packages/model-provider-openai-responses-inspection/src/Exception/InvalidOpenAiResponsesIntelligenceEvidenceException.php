<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponsesInspection\Diagnostic\OpenAiResponsesInspectionDiagnostic;
use Throwable;

/** Rejects malformed construction-only Graph, role, or verification evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidOpenAiResponsesIntelligenceEvidenceException extends OpenAiResponsesInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(OpenAiResponsesInspectionDiagnostic::InvalidIntelligenceEvidence, self::safe($component), $previous);
    }
}
