<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Grammar;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Enumerates the complete version-1 structured node language. */
#[Api(ApiStatus::Experimental)]
enum ViewNodeKind: string
{
    case Text = 'text';
    case Value = 'value';
    case Element = 'element';
    case Each = 'each';
}
