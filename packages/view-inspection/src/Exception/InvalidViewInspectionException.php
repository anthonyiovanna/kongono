<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewInspection\Diagnostic\ViewInspectionDiagnostic;
use Throwable;

/** Rejects malformed package-owned inspection values. */
#[Api(ApiStatus::Experimental)]
final class InvalidViewInspectionException extends ViewInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(ViewInspectionDiagnostic::InvalidInspection, self::safe($component), $previous);
    }
}
