<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Labels retention intent without asserting storage or duration. */
#[Api(ApiStatus::Experimental)]
enum SecurityAuditRetentionIntent: string
{
    case Short = 'short';
    case Standard = 'standard';
    case Extended = 'extended';
}
