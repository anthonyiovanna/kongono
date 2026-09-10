<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Credential;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names lease lifecycle evidence without implementing lease transitions. */
#[Api(ApiStatus::Experimental)]
enum CredentialLeaseState: string
{
    case Acquired = 'acquired';
    case InUse = 'in_use';
    case Retiring = 'retiring';
    case Retired = 'retired';
    case Uncertain = 'uncertain';
}
