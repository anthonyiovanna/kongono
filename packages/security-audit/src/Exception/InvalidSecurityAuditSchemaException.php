<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityAudit\Diagnostic\SecurityAuditDiagnostic;

/** Reports invalid bounded security-audit field and schema declarations. */
#[Api(ApiStatus::Experimental)]
final class InvalidSecurityAuditSchemaException extends SecurityAuditException
{
    public static function field(): self
    {
        return new self(SecurityAuditDiagnostic::Schema);
    }

    public static function schema(): self
    {
        return new self(SecurityAuditDiagnostic::Schema);
    }
}
