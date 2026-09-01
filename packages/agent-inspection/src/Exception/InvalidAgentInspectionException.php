<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Exception;

use Kongono\AgentInspection\Diagnostic\AgentInspectionDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports an invalid bounded inspection request, value, or serialization. */
#[Api(ApiStatus::Experimental)]
final class InvalidAgentInspectionException extends AgentInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(AgentInspectionDiagnostic::InvalidInspection, self::safe($component), $previous);
    }
}
