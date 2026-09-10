<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceInspection\Diagnostic\ExternalServiceInspectionDiagnostic;
use Throwable;

/** Rejects malformed package-owned inspection values. */
#[Api(ApiStatus::Experimental)]
final class InvalidExternalServiceInspectionException extends ExternalServiceInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(ExternalServiceInspectionDiagnostic::InvalidInspection, self::safe($component), $previous);
    }
}
