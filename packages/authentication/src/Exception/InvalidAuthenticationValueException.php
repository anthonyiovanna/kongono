<?php

declare(strict_types=1);

namespace Kongono\Authentication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authentication\Diagnostic\AuthenticationDiagnostic;

/** Reports one authentication contract violation without retaining rejected input. */
#[Api(ApiStatus::Experimental)]
final class InvalidAuthenticationValueException extends AuthenticationException
{
    private function __construct(AuthenticationDiagnostic $diagnostic)
    {
        parent::__construct($diagnostic);
    }

    public static function validation(): self
    {
        return new self(AuthenticationDiagnostic::Validation);
    }

    public static function registration(): self
    {
        return new self(AuthenticationDiagnostic::Registration);
    }

    public static function evidence(): self
    {
        return new self(AuthenticationDiagnostic::Evidence);
    }

    public static function verification(): self
    {
        return new self(AuthenticationDiagnostic::Verification);
    }

    public static function freshness(): self
    {
        return new self(AuthenticationDiagnostic::Freshness);
    }

    public static function strength(): self
    {
        return new self(AuthenticationDiagnostic::Strength);
    }
}
