<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityHttp\Exception\InvalidBrowserSessionValueException;
use SensitiveParameter;

/** Carries exact opaque CSRF proof bytes only to the selected verifier boundary. */
#[Api(ApiStatus::Experimental)]
final readonly class BrowserSessionCsrfProof
{
    public function __construct(#[SensitiveParameter] private string $value)
    {
        if (1 !== preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $value)) {
            throw InvalidBrowserSessionValueException::wireSyntax();
        }
    }

    /** Crossing this boundary exposes sensitive bytes to the selected verifier. */
    public function sensitiveValue(): string
    {
        return $this->value;
    }
}
