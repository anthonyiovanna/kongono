<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityHttp\Diagnostic\SecurityHttpDiagnostic;

/** Collapses every presented-session non-success without anonymous downgrade. */
#[Api(ApiStatus::Experimental)]
final class BrowserSessionAuthenticationException extends SecurityHttpException
{
    private function __construct()
    {
        parent::__construct(SecurityHttpDiagnostic::AuthenticationRejected);
    }

    public static function rejected(): self
    {
        return new self();
    }
}
