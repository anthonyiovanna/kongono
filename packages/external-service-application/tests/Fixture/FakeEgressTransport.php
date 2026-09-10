<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Fixture;

use Closure;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportRequest;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportSession;

final class FakeEgressTransport implements EgressTransport
{
    public int $capabilityCalls = 0;
    public int $openCalls = 0;
    public ?EgressTransportRequest $request = null;

    public function __construct(
        private TransportCapabilitySet $capabilities,
        private EgressTransportSession $session,
        private ?Closure $observer = null,
    ) {}

    public function capabilities(): TransportCapabilitySet
    {
        ++$this->capabilityCalls;

        return $this->capabilities;
    }

    public function open(EgressTransportRequest $request): EgressTransportSession
    {
        ++$this->openCalls;
        $this->request = $request;
        if (null !== $this->observer) {
            ($this->observer)('transport.open');
        }

        return $this->session;
    }
}
