<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Grant;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Records an explicit application-owned grant or denial without itself granting authority. */
#[Api(ApiStatus::Experimental)]
enum CapabilityDisposition: string
{
    case Granted = 'granted';
    case Denied = 'denied';
}
