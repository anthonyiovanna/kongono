<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries the scalar kind derived from current mapping evidence. */
#[Api(ApiStatus::Experimental)]
enum FieldValueKind: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Float = 'float';
    case Text = 'text';
    case Binary = 'binary';
}
