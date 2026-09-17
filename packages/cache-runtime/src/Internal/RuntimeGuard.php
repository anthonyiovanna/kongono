<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheRuntime\Exception\CacheRuntimeOperationException;

/** Owns the minimum instance-local active-operation state. */
#[Api(ApiStatus::Internal)]
final class RuntimeGuard
{
    private bool $active = false;

    public function enter(string $operation): void
    {
        if ($this->active) {
            throw CacheRuntimeOperationException::reentrant($operation);
        }
        $this->active = true;
    }

    public function leave(): void
    {
        $this->active = false;
    }
}
