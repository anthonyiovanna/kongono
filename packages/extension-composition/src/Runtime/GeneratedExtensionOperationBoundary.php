<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines the lifecycle-neutral hosted boundary implemented by generated operation assemblies. */
#[Api(ApiStatus::Experimental)]
interface GeneratedExtensionOperationBoundary
{
    /** @return list<object> */
    public function operationExtensions(): array;
}
