<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityHttp\Diagnostic\SecurityHttpDiagnostic;

/** Reports invalid fixed-profile values without retaining rejected bytes. */
#[Api(ApiStatus::Experimental)]
final class InvalidBrowserSessionValueException extends SecurityHttpException
{
    private function __construct(SecurityHttpDiagnostic $diagnostic)
    {
        parent::__construct($diagnostic);
    }

    public static function wireSyntax(): self
    {
        return new self(SecurityHttpDiagnostic::InvalidWireValue);
    }

    public static function evidenceShape(): self
    {
        return new self(SecurityHttpDiagnostic::InvalidWireValue);
    }

    public static function trustedOrigin(): self
    {
        return new self(SecurityHttpDiagnostic::InvalidTrustedOrigin);
    }
}
