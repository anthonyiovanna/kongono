<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Describes architecture without asserting trust, admission, isolation, or authority. */
#[Api(ApiStatus::Experimental)]
enum ExtensionCategory: string
{
    case Core = 'core';
    case TrustedInProcess = 'trusted_in_process';
    case Sandboxed = 'sandboxed';
    case External = 'external';
}
