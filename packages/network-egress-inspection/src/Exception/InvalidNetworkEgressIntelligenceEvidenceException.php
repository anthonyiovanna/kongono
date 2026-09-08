<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressInspection\Diagnostic\NetworkEgressInspectionDiagnostic;
use Throwable;

/** Rejects malformed construction-only Graph, role, or verification evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidNetworkEgressIntelligenceEvidenceException extends NetworkEgressInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(NetworkEgressInspectionDiagnostic::InvalidIntelligenceEvidence, self::safe($component), $previous);
    }
}
