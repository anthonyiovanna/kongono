<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityRuntime\Diagnostic\SecurityRuntimeDiagnostic;

/** Reports rejected authentication verification without retaining its values. */
#[Api(ApiStatus::Experimental)]
final class InvalidSecurityContextVerificationException extends SecurityRuntimeException
{
    private function __construct(string $failureCategory)
    {
        parent::__construct(SecurityRuntimeDiagnostic::InvalidVerification, $failureCategory);
    }

    public static function outcome(): self
    {
        return new self('outcome');
    }

    public static function facts(): self
    {
        return new self('facts');
    }

    public static function principalIdentity(): self
    {
        return new self('principal_identity');
    }
}
