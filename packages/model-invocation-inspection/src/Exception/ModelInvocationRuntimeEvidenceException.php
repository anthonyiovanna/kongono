<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationInspection\Diagnostic\ModelInvocationInspectionDiagnostic;
use Throwable;

/** Reports invalid detached completed runtime relationships. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationRuntimeEvidenceException extends ModelInvocationInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(ModelInvocationInspectionDiagnostic::CompletedRuntimeMismatch, self::safe($component), $previous);
    }
}
