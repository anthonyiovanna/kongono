<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceInspection\Diagnostic\ExternalServiceInspectionDiagnostic;
use Throwable;

/** Reports one exact supplied profile, binding, artifact, or policy failure. */
#[Api(ApiStatus::Experimental)]
final class ExternalServiceInspectionBindingException extends ExternalServiceInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(ExternalServiceInspectionDiagnostic::BindingMismatch, self::safe($component), $previous);
    }
}
