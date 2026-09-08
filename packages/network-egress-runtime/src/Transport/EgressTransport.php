<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Transport;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;

/** Opens one exact-address session through a directly supplied transport. */
#[Api(ApiStatus::Experimental)]
interface EgressTransport
{
    public function capabilities(): TransportCapabilitySet;

    public function open(EgressTransportRequest $request): EgressTransportSession;
}
