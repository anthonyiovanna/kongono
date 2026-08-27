<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\Diagnostic\IdentityAccessDiagnostic;

/** Reports one neutral contract-value violation without retaining rejected input. */
#[Api(ApiStatus::Experimental)]
final class InvalidIdentityAccessValueException extends IdentityAccessException
{
    private function __construct(IdentityAccessDiagnostic $diagnostic)
    {
        parent::__construct($diagnostic);
    }

    public static function principal(): self
    {
        return new self(IdentityAccessDiagnostic::Principal);
    }

    public static function authentication(): self
    {
        return new self(IdentityAccessDiagnostic::Authentication);
    }

    public static function authorization(): self
    {
        return new self(IdentityAccessDiagnostic::Authorization);
    }

    public static function resource(): self
    {
        return new self(IdentityAccessDiagnostic::Resource);
    }

    public static function securityContext(): self
    {
        return new self(IdentityAccessDiagnostic::SecurityContext);
    }
}
