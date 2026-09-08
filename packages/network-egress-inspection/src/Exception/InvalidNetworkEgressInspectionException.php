<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressInspection\Diagnostic\NetworkEgressInspectionDiagnostic;
use Throwable;

/** Rejects malformed package-owned inspection values. */
#[Api(ApiStatus::Experimental)]
final class InvalidNetworkEgressInspectionException extends NetworkEgressInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(NetworkEgressInspectionDiagnostic::InvalidInspection, self::safe($component), $previous);
    }
}
