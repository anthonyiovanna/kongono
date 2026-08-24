<?php

declare(strict_types=1);

namespace Kongono\Composition\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Defines the lifecycle-only boundary shared by generated application assemblies. */
#[Api(ApiStatus::Experimental)]
interface GeneratedApplicationBoundary
{
    public function beginOperation(): GeneratedOperationBoundary;
    public function shutdown(?Throwable $primaryFailure = null): void;
    public function isRetired(): bool;
}
