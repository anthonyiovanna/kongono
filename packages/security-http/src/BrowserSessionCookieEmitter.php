<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use SensitiveParameter;

/** Emits one exact host-only secure cookie value without mutating a response. */
#[Api(ApiStatus::Experimental)]
final class BrowserSessionCookieEmitter
{
    public function issue(#[SensitiveParameter] BrowserSessionIdentifier $identifier): string
    {
        return BrowserSessionProfile::COOKIE_NAME . '=' . $identifier->sensitiveValue()
            . '; Path=/; Secure; HttpOnly; SameSite=Strict';
    }

    public function expire(): string
    {
        return BrowserSessionProfile::COOKIE_NAME
            . '=; Max-Age=0; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/; Secure; HttpOnly; SameSite=Strict';
    }
}
