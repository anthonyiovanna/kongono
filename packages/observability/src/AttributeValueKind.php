<?php

declare(strict_types=1);

namespace Kongono\Observability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact native scalar domain of one signal attribute value. */
#[Api(ApiStatus::Experimental)]
enum AttributeValueKind: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case FloatingPoint = 'floating_point';
    case Text = 'text';
}
