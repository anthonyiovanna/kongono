<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Fixture;

use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportRequest;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportSession;

final class FakeTransport implements EgressTransport
{
    public int $capabilityCalls = 0;
    public int $openCalls = 0;
    public ?EgressTransportRequest $request = null;

    /** @param non-empty-list<TransportCapabilitySet> $capabilityAnswers */
    public function __construct(private array $capabilityAnswers, private EgressTransportSession $session) {}

    public function capabilities(): TransportCapabilitySet
    {
        $position = min($this->capabilityCalls, count($this->capabilityAnswers) - 1);
        ++$this->capabilityCalls;

        return $this->capabilityAnswers[$position];
    }

    public function open(EgressTransportRequest $request): EgressTransportSession
    {
        ++$this->openCalls;
        $this->request = $request;

        return $this->session;
    }
}
