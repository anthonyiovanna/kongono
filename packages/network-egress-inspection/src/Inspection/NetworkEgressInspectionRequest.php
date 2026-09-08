<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressWiring;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressContract\Operation\EgressOperation;
use Kongono\NetworkEgressInspection\Exception\InvalidNetworkEgressInspectionException;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;

/** Retains one explicit profile/wiring pair and optional paired detached runtime evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressInspectionRequest
{
    public function __construct(
        private NetworkEgressApplicationProfile $profile,
        private GeneratedNetworkEgressWiring $wiring,
        private ?EgressOperation $operation = null,
        private ?EgressRuntimeResult $runtimeResult = null,
    ) {
        if ((null === $operation) !== (null === $runtimeResult)) {
            throw InvalidNetworkEgressInspectionException::invalid('request');
        }
    }

    public function profile(): NetworkEgressApplicationProfile
    {
        return $this->profile;
    }

    public function wiring(): GeneratedNetworkEgressWiring
    {
        return $this->wiring;
    }

    public function operation(): ?EgressOperation
    {
        return $this->operation;
    }

    public function runtimeResult(): ?EgressRuntimeResult
    {
        return $this->runtimeResult;
    }
}
