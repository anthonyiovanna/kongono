<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines the closed content-type vocabulary for completed HTML views. */
#[Api(ApiStatus::Experimental)]
enum ViewContentType: string
{
    case HtmlUtf8 = 'text/html; charset=utf-8';
}
