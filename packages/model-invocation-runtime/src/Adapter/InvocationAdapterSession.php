<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Synchronous pull session whose accepted emissions are runtime-backpressured. */
#[Api(ApiStatus::Experimental)]
interface InvocationAdapterSession
{
    public function next(): ?InvocationAdapterEmission;

    public function close(): void;
}
