<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationInspection\Diagnostic\ModelInvocationInspectionDiagnostic;
use Throwable;

/** Reports one exact supplied profile/wiring binding failure. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationInspectionBindingException extends ModelInvocationInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(ModelInvocationInspectionDiagnostic::BindingMismatch, self::safe($component), $previous);
    }
}
