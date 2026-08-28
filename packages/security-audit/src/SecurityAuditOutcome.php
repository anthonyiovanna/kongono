<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the three exact security-audit evidence outcomes. */
#[Api(ApiStatus::Experimental)]
enum SecurityAuditOutcome: string
{
    case Succeeded = 'succeeded';
    case Denied = 'denied';
    case Failed = 'failed';
}
