<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceInspection\Diagnostic\ExternalServiceInspectionDiagnostic;
use Throwable;

/** Reports invalid detached service, credential, or egress evidence without exposing foreign content. */
#[Api(ApiStatus::Experimental)]
final class ExternalServiceResultEvidenceException extends ExternalServiceInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(ExternalServiceInspectionDiagnostic::ResultEvidenceMismatch, self::safe($component), $previous);
    }
}
