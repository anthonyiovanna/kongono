<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Limits eligible Graph-fact audiences without granting authorization. */
#[Api(ApiStatus::Experimental)]
enum FactVisibility: string
{
    case Public = 'public';
    case Application = 'application';
    case FrameworkInternal = 'framework_internal';
}
