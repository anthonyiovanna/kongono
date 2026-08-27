<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies the only two neutral Phase 9B security-context shapes. */
#[Api(ApiStatus::Experimental)]
enum SecurityContextState: string
{
    case Anonymous = 'anonymous';
    case Authenticated = 'authenticated';
}
