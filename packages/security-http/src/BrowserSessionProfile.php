<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns the sole fixed Phase 9F browser-session transport identifiers. */
#[Api(ApiStatus::Experimental)]
final class BrowserSessionProfile
{
    public const string NAME = 'browser.session.v1';
    public const string COOKIE_NAME = '__Host-kongono_session';
    public const string CSRF_HEADER_NAME = 'X-Kongono-CSRF';

    private function __construct() {}
}
