<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationInspection\Diagnostic\ModelInvocationInspectionDiagnostic;
use Throwable;

/** Rejects malformed package-owned inspection values. */
#[Api(ApiStatus::Internal)]
final class InvalidModelInvocationInspectionException extends ModelInvocationInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(ModelInvocationInspectionDiagnostic::InvalidInspection, self::safe($component), $previous);
    }
}
