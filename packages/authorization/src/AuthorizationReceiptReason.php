<?php

declare(strict_types=1);

namespace Kongono\Authorization;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies why one bounded authorization receipt exists. */
#[Api(ApiStatus::Experimental)]
enum AuthorizationReceiptReason: string
{
    case Evaluated = 'evaluated';
    case PolicyMismatch = 'policy_mismatch';
    case UnsupportedPolicy = 'unsupported_policy';
}
