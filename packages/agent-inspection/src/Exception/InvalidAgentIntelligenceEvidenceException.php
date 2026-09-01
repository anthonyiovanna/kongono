<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Exception;

use Kongono\AgentInspection\Diagnostic\AgentInspectionDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports invalid bounded Graph, role-addressed, or verification evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidAgentIntelligenceEvidenceException extends AgentInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(AgentInspectionDiagnostic::InvalidIntelligenceEvidence, self::safe($component), $previous);
    }
}
