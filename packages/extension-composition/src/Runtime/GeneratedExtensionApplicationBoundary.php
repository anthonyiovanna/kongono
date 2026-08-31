<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines the lifecycle-neutral hosted boundary implemented by generated application assemblies. */
#[Api(ApiStatus::Experimental)]
interface GeneratedExtensionApplicationBoundary
{
    /** @param list<object> $applicationProviders */
    public static function startHosted(array $applicationProviders): self;

    /** @param list<object> $operationProviders */
    public function beginHostedOperation(array $operationProviders): GeneratedExtensionOperationBoundary;

    /** @return list<object> */
    public function applicationExtensions(): array;
}
