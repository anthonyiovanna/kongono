<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Exception;

use Kongono\AgentInspection\Diagnostic\AgentInspectionDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports bounded completed runtime evidence that cannot be safely attributed. */
#[Api(ApiStatus::Experimental)]
final class InvalidCompletedRuntimeEvidenceException extends AgentInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(AgentInspectionDiagnostic::InvalidRuntimeEvidence, self::safe($component), $previous);
    }
}
