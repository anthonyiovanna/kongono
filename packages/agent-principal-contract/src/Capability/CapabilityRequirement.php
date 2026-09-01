<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Capability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares request necessity without granting or implementing capability access. */
#[Api(ApiStatus::Experimental)]
enum CapabilityRequirement: string
{
    case Required = 'required';
    case Optional = 'optional';
}
