<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewInspection\Diagnostic\ViewInspectionDiagnostic;
use Throwable;

/** Rejects malformed construction-only Graph, role, or verification evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidViewIntelligenceEvidenceException extends ViewInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(ViewInspectionDiagnostic::InvalidIntelligenceEvidence, self::safe($component), $previous);
    }
}
