<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares inert credential posture without looking up credential material. */
#[Api(ApiStatus::Experimental)]
enum CredentialMode: string
{
    case None = 'none';
    case OperationScoped = 'operation_scoped';
}
