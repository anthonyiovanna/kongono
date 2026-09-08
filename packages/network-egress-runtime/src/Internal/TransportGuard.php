<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeRequest;

/** Validates one directly supplied transport without selection or discovery. */
#[Api(ApiStatus::Internal)]
final class TransportGuard
{
    public static function validate(EgressRuntimeRequest $request, TransportCapabilitySet $first, TransportCapabilitySet $second): void
    {
        if ($first->all() !== $second->all()) {
            throw InvalidEgressRuntimeException::invalid('capability_instability');
        }
        foreach ($request->policySource()->requiredCapabilities()->all() as $required) {
            if (!$first->contains($required)) {
                throw InvalidEgressRuntimeException::invalid('transport_capability');
            }
        }
        if (!$first->contains(TransportCapability::Http11) && !$first->contains(TransportCapability::Http2) && !$first->contains(TransportCapability::Http3)) {
            throw InvalidEgressRuntimeException::invalid('transport_capability');
        }
        if (OriginScheme::Https === $request->operation()->origin()->scheme() && !$first->contains(TransportCapability::Tls)) {
            throw InvalidEgressRuntimeException::invalid('transport_capability');
        }
    }

    public static function validateProtocol(NegotiatedProtocol $protocol, TransportCapabilitySet $capabilities): void
    {
        $required = match ($protocol) {
            NegotiatedProtocol::Unknown => null,
            NegotiatedProtocol::Http11 => TransportCapability::Http11,
            NegotiatedProtocol::Http2 => TransportCapability::Http2,
            NegotiatedProtocol::Http3 => TransportCapability::Http3,
        };
        if (null !== $required && !$capabilities->contains($required)) {
            throw InvalidEgressRuntimeException::invalid('protocol_capability');
        }
    }

    private function __construct() {}
}
