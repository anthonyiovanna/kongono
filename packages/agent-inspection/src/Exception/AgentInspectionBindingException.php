<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Exception;

use Kongono\AgentInspection\Diagnostic\AgentInspectionDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports one exact profile, plan, declaration, or wiring binding mismatch. */
#[Api(ApiStatus::Experimental)]
final class AgentInspectionBindingException extends AgentInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(AgentInspectionDiagnostic::BindingMismatch, self::safe($component), $previous);
    }
}
