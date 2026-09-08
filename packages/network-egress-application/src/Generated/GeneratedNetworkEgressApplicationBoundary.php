<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplication;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;

/** Fixed boundary implemented only by exact generated single-profile wiring. */
#[Api(ApiStatus::Experimental)]
interface GeneratedNetworkEgressApplicationBoundary
{
    public function start(NetworkEgressApplicationProfile $profile, EgressResolver $resolver, EgressTransport $transport): NetworkEgressApplication;
}
