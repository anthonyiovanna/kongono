<?php

declare(strict_types=1);

namespace Kongono\Authorization\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\Diagnostic\AuthorizationDiagnostic;

/** Reports one authorization contract violation without retaining rejected input. */
#[Api(ApiStatus::Experimental)]
final class InvalidAuthorizationValueException extends AuthorizationException
{
    private function __construct(AuthorizationDiagnostic $diagnostic)
    {
        parent::__construct($diagnostic);
    }

    public static function validation(): self
    {
        return new self(AuthorizationDiagnostic::Validation);
    }

    public static function registration(): self
    {
        return new self(AuthorizationDiagnostic::Registration);
    }

    public static function receipt(): self
    {
        return new self(AuthorizationDiagnostic::Receipt);
    }
}
