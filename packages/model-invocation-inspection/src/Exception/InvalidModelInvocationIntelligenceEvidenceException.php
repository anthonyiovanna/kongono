<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationInspection\Diagnostic\ModelInvocationInspectionDiagnostic;
use Throwable;

/** Rejects malformed construction-only Graph, role, or verification evidence. */
#[Api(ApiStatus::Internal)]
final class InvalidModelInvocationIntelligenceEvidenceException extends ModelInvocationInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(ModelInvocationInspectionDiagnostic::InvalidIntelligenceEvidence, self::safe($component), $previous);
    }
}
