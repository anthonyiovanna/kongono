<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the closed failure classes produced before candidate normalization. */
#[Api(ApiStatus::Internal)]
enum AddressInfoLookupFailure: string
{
    case LookupFailed = 'system_resolver_lookup_failed';
    case ResultInvalid = 'system_resolver_result_invalid';
    case CandidateLimit = 'system_resolver_candidate_limit';
    case NoAddress = 'system_resolver_no_address';
}
