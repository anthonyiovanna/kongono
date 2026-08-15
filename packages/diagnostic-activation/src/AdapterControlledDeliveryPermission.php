<?php

declare(strict_types=1);

namespace Kongono\DiagnosticActivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Carries one outer adapter decision for the current delivery boundary.
 *
 * A future adapter selects this immutable value from evidence it exclusively owns.
 * Allowed does not prove trust or authorize diagnostic access, projection, rendering,
 * or delivery without independent affirmative trusted-bootstrap permission.
 */
#[Api(ApiStatus::Experimental)]
enum AdapterControlledDeliveryPermission
{
    case Denied;
    case Allowed;

    public function allowsTrustedPresentation(): bool
    {
        return self::Allowed === $this;
    }
}
