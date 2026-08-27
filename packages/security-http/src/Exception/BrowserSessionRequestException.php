<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityHttp\Diagnostic\SecurityHttpDiagnostic;

/** Reports fail-closed request rejection without retaining request values. */
#[Api(ApiStatus::Experimental)]
final class BrowserSessionRequestException extends SecurityHttpException
{
    private function __construct(SecurityHttpDiagnostic $diagnostic)
    {
        parent::__construct($diagnostic);
    }

    public static function cookie(): self
    {
        return new self(SecurityHttpDiagnostic::InvalidCookie);
    }

    public static function transport(): self
    {
        return new self(SecurityHttpDiagnostic::TransportRejected);
    }

    public static function csrf(): self
    {
        return new self(SecurityHttpDiagnostic::CsrfRejected);
    }
}
