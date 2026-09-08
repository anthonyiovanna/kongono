<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Transport;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressCurl\Internal\CurlEgressTransportSession;
use Kongono\NetworkEgressCurl\Internal\CurlEnvironmentGuard;
use Kongono\NetworkEgressCurl\Internal\CurlRequestGuard;
use Kongono\NetworkEgressCurl\Internal\NativeCurlExecutor;
use Kongono\NetworkEgressCurl\Profile\CurlTransportProfile;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportRequest;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportSession;
use SensitiveParameter;

/** Opens one fresh explicit-address cURL HTTP/1.1 transport session. */
#[Api(ApiStatus::Experimental)]
final class CurlEgressTransport implements EgressTransport
{
    private readonly TransportCapabilitySet $capabilities;

    public function __construct(#[SensitiveParameter] private readonly CurlTransportProfile $profile)
    {
        CurlEnvironmentGuard::assertSupported();
        $this->capabilities = new TransportCapabilitySet([
            TransportCapability::Http11,
            TransportCapability::Tls,
            TransportCapability::RequestStreaming,
        ]);
    }

    public function capabilities(): TransportCapabilitySet
    {
        return $this->capabilities;
    }

    public function open(#[SensitiveParameter] EgressTransportRequest $request): EgressTransportSession
    {
        $bodyReader = CurlRequestGuard::admit($request, $this->profile);

        return new CurlEgressTransportSession(new NativeCurlExecutor($request, $this->profile, $bodyReader));
    }
}
