<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Transport;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Operation\EgressOperation;
use Kongono\NetworkEgressRuntime\Address\ResolvedAddress;

/** Binds the accepted operation to the exact address and attempt identity. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressTransportRequest
{
    public function __construct(
        private EgressOperation $operation,
        private ResolvedAddress $selectedAddress,
        private EgressAttemptIdentity $attemptIdentity,
    ) {}

    public function operation(): EgressOperation
    {
        return $this->operation;
    }
    public function selectedAddress(): ResolvedAddress
    {
        return $this->selectedAddress;
    }
    public function attemptIdentity(): EgressAttemptIdentity
    {
        return $this->attemptIdentity;
    }
}
