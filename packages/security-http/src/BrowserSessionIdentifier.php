<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityHttp\Exception\InvalidBrowserSessionValueException;
use SensitiveParameter;

/** Carries exact opaque session bytes across only the verifier and cookie boundaries. */
#[Api(ApiStatus::Experimental)]
final readonly class BrowserSessionIdentifier
{
    public function __construct(#[SensitiveParameter] private string $value)
    {
        if (1 !== preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $value)) {
            throw InvalidBrowserSessionValueException::wireSyntax();
        }
    }

    /** Crossing this boundary exposes sensitive bytes to a selected verifier or cookie emitter. */
    public function sensitiveValue(): string
    {
        return $this->value;
    }
}
