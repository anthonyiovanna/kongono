<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressInspection\Diagnostic\NetworkEgressInspectionDiagnostic;
use Throwable;

/** Reports neutral projection failure without recording or foreign messages. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressInspectionProjectionException extends NetworkEgressInspectionException
{
    public static function failed(string $component, ?Throwable $previous = null): self
    {
        return new self(NetworkEgressInspectionDiagnostic::ProjectionFailure, self::safe($component), $previous);
    }
}
