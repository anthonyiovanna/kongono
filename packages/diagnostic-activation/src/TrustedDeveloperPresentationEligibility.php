<?php

declare(strict_types=1);

namespace Kongono\DiagnosticActivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Calculates trusted-presentation eligibility from two already-established permissions.
 *
 * The value is scoped to one current delivery. A true result records only the pure
 * conjunction; it does not prove either input's provenance or enforce presentation access.
 */
#[Api(ApiStatus::Experimental)]
final readonly class TrustedDeveloperPresentationEligibility
{
    public function __construct(
        private TrustedDeveloperBootstrapPermission $bootstrapPermission,
        private AdapterControlledDeliveryPermission $deliveryPermission,
    ) {}

    public function allowsTrustedPresentation(): bool
    {
        return $this->bootstrapPermission->allowsTrustedPresentation()
            && $this->deliveryPermission->allowsTrustedPresentation();
    }
}
