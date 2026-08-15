<?php

declare(strict_types=1);

namespace Kongono\DiagnosticActivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Carries one already-validated server-bootstrap decision for trusted presentation.
 *
 * A future composition boundary supplies this immutable value to an adapter before
 * it serves work. Allowed does not establish the adapter's independent delivery
 * permission and does not authorize source access, projection, rendering, or delivery.
 */
#[Api(ApiStatus::Experimental)]
enum TrustedDeveloperBootstrapPermission
{
    case Denied;
    case Allowed;

    public function allowsTrustedPresentation(): bool
    {
        return self::Allowed === $this;
    }
}
