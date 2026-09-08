<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressInspection\Diagnostic\NetworkEgressInspectionDiagnostic;
use Throwable;

/** Reports invalid detached operation/runtime evidence without exposing foreign content. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressRuntimeEvidenceException extends NetworkEgressInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(NetworkEgressInspectionDiagnostic::RuntimeEvidenceMismatch, self::safe($component), $previous);
    }
}
