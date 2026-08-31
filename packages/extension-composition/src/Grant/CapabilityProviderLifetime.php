<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Grant;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares the generated ownership boundary of one explicitly supplied provider object. */
#[Api(ApiStatus::Experimental)]
enum CapabilityProviderLifetime: string
{
    case Application = 'application';
    case Operation = 'operation';
}
