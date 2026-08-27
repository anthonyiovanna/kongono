<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies a neutral authentication outcome without implying authorization. */
#[Api(ApiStatus::Experimental)]
enum AuthenticationOutcomeKind: string
{
    case Authenticated = 'authenticated';
    case Absent = 'absent';
    case Invalid = 'invalid';
    case Expired = 'expired';
    case Replayed = 'replayed';
    case Unsupported = 'unsupported';
    case Ambiguous = 'ambiguous';
    case Unavailable = 'unavailable';
}
