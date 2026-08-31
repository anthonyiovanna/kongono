<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Capability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares required or optional request intent without creating a grant. */
#[Api(ApiStatus::Experimental)]
enum CapabilityRequirement: string
{
    case Required = 'required';
    case Optional = 'optional';
}
