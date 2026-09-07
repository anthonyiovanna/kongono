<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationInspection\Diagnostic\ModelInvocationInspectionDiagnostic;
use Throwable;

/** Reports neutral projection failure without recording or foreign messages. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationInspectionProjectionException extends ModelInvocationInspectionException
{
    public static function failed(string $component, ?Throwable $previous = null): self
    {
        return new self(ModelInvocationInspectionDiagnostic::ProjectionFailure, self::safe($component), $previous);
    }
}
