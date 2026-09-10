<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Lifecycle;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names the closed lifecycle of one credential lease runtime operation. */
#[Api(ApiStatus::Experimental)]
enum CredentialLeaseRuntimeState: string
{
    case Prepared = 'prepared';
    case Acquiring = 'acquiring';
    case Acquired = 'acquired';
    case Using = 'using';
    case Retiring = 'retiring';
    case Retired = 'retired';
    case Failed = 'failed';
    case Uncertain = 'uncertain';
}
