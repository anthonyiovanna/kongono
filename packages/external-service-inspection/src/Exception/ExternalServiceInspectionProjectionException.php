<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceInspection\Diagnostic\ExternalServiceInspectionDiagnostic;
use Throwable;

/** Reports neutral projection failure without recording or foreign messages. */
#[Api(ApiStatus::Experimental)]
final class ExternalServiceInspectionProjectionException extends ExternalServiceInspectionException
{
    public static function failed(string $component, ?Throwable $previous = null): self
    {
        return new self(ExternalServiceInspectionDiagnostic::ProjectionFailure, self::safe($component), $previous);
    }
}
