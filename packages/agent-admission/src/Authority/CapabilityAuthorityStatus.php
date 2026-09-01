<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Authority;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Preserves one exact non-executable capability decision status. */
#[Api(ApiStatus::Experimental)]
enum CapabilityAuthorityStatus: string
{
    case Permitted = 'permitted';
    case NotRequested = 'not_requested';
    case MissingAuthorization = 'missing_authorization';
    case Denied = 'denied';
    case Indeterminate = 'indeterminate';
    case Undeclared = 'undeclared';
}
