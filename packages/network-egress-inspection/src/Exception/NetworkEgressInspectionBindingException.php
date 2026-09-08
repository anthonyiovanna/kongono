<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressInspection\Diagnostic\NetworkEgressInspectionDiagnostic;
use Throwable;

/** Reports one exact supplied profile/wiring binding failure. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressInspectionBindingException extends NetworkEgressInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(NetworkEgressInspectionDiagnostic::BindingMismatch, self::safe($component), $previous);
    }
}
