<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewInspection\Diagnostic\ViewInspectionDiagnostic;
use Throwable;

/** Reports incoherent profile, current-build, or artifact bindings. */
#[Api(ApiStatus::Experimental)]
final class ViewInspectionBindingException extends ViewInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(ViewInspectionDiagnostic::ProfileBindingMismatch, self::safe($component), $previous);
    }
}
