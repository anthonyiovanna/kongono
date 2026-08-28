<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityAudit\Diagnostic\SecurityAuditDiagnostic;
use Throwable;

/** Preserves one external recorder failure without exposing its message. */
#[Api(ApiStatus::Experimental)]
final class SecurityAuditRecordingException extends SecurityAuditException
{
    public static function failed(Throwable $previous): self
    {
        return new self(SecurityAuditDiagnostic::Recording, $previous);
    }
}
