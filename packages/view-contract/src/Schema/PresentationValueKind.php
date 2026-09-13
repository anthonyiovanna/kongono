<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Enumerates the closed presentation-value domain. */
#[Api(ApiStatus::Experimental)]
enum PresentationValueKind: string
{
    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Null = 'null';
    case List = 'list';
    case Record = 'record';
}
