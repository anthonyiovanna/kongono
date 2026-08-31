<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Lifecycle;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares intended ownership scope without defining hosting or execution behavior. */
#[Api(ApiStatus::Experimental)]
enum ExtensionLifecycle: string
{
    case Application = 'application';
    case Operation = 'operation';
}
