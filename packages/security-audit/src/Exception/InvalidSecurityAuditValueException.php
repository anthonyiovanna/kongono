<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityAudit\Diagnostic\SecurityAuditDiagnostic;

/** Reports fields that do not match an exact security-audit schema. */
#[Api(ApiStatus::Experimental)]
final class InvalidSecurityAuditValueException extends SecurityAuditException
{
    public static function attributes(): self
    {
        return new self(SecurityAuditDiagnostic::Value);
    }
}
