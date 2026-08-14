<?php

declare(strict_types=1);

namespace Kongono\Kernel\Lifecycle;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Represents the three terminally ordered states of one Kernel instance.
 *
 * The enum is intentionally not serialized and contains no transitional or failure
 * state. Shutdown is terminal for the owning Kernel lifetime. Stability:
 * Experimental.
 */
#[Api(ApiStatus::Experimental)]
enum KernelState
{
    case Unbooted;
    case Booted;
    case Shutdown;
}
