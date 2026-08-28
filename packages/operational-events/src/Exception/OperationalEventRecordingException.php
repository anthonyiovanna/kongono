<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OperationalEvents\Diagnostic\OperationalEventsDiagnostic;
use Throwable;

/** Preserves one external recorder failure without exposing its message. */
#[Api(ApiStatus::Experimental)]
final class OperationalEventRecordingException extends OperationalEventException
{
    public static function failed(Throwable $previous): self
    {
        return new self(OperationalEventsDiagnostic::Recording, $previous);
    }
}
