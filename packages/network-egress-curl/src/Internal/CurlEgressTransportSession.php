<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressCurl\Exception\CurlTransportOperationException;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportSession;
use Throwable;

/** Exposes one lazy terminal emission and deterministic explicit retirement. */
#[Api(ApiStatus::Internal)]
final class CurlEgressTransportSession implements EgressTransportSession
{
    private readonly CurlSessionGuard $guard;

    public function __construct(private readonly CurlExecutor $executor)
    {
        $this->guard = new CurlSessionGuard();
    }

    public function next(): ?EgressTransportEmission
    {
        if (!$this->guard->begin()) {
            return null;
        }
        try {
            return $this->executor->execute()->emission();
        } finally {
            $this->guard->finish();
        }
    }

    public function close(): void
    {
        if (!$this->guard->close()) {
            return;
        }
        try {
            $this->executor->close();
        } catch (Throwable $throwable) {
            throw CurlTransportOperationException::cleanup($throwable);
        }
    }
}
