<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceInspection\Diagnostic\ExternalServiceInspectionDiagnostic;
use Throwable;

/** Rejects malformed construction-only Graph, role, or verification evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidExternalServiceIntelligenceEvidenceException extends ExternalServiceInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(ExternalServiceInspectionDiagnostic::InvalidIntelligenceEvidence, self::safe($component), $previous);
    }
}
