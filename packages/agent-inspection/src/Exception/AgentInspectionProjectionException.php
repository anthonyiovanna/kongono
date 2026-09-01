<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Exception;

use Kongono\AgentInspection\Diagnostic\AgentInspectionDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports failure to construct one neutral bounded projection. */
#[Api(ApiStatus::Experimental)]
final class AgentInspectionProjectionException extends AgentInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(AgentInspectionDiagnostic::InvalidProjection, self::safe($component), $previous);
    }
}
