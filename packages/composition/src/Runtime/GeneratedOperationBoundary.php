<?php

declare(strict_types=1);

namespace Kongono\Composition\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Defines the lifecycle-only boundary shared by generated operation assemblies. */
#[Api(ApiStatus::Experimental)]
interface GeneratedOperationBoundary
{
    public function close(?Throwable $primaryFailure = null): void;
    public function isClosed(): bool;
}
