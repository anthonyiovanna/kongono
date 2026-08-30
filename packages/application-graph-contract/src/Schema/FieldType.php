<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines the exact version-1 field value kinds. */
#[Api(ApiStatus::Experimental)]
enum FieldType: string
{
    case Text = 'text';
}
