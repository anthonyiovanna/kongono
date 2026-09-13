<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewInspection\Diagnostic\ViewInspectionDiagnostic;
use Throwable;

/** Reports neutral projection failure without recording or foreign messages. */
#[Api(ApiStatus::Experimental)]
final class ViewInspectionProjectionException extends ViewInspectionException
{
    public static function failed(string $component, ?Throwable $previous = null): self
    {
        return new self(ViewInspectionDiagnostic::ProjectionFailure, self::safe($component), $previous);
    }
}
