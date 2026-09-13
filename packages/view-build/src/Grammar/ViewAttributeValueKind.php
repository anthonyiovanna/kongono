<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Grammar;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Enumerates the two closed quoted-attribute value variants. */
#[Api(ApiStatus::Experimental)]
enum ViewAttributeValueKind: string
{
    case Literal = 'literal';
    case Value = 'value';
}
