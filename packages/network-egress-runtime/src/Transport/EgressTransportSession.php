<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Transport;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Exposes synchronous pull-based progress and deterministic retirement. */
#[Api(ApiStatus::Experimental)]
interface EgressTransportSession
{
    public function next(): ?EgressTransportEmission;

    public function close(): void;
}
