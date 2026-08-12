<?php

declare(strict_types=1);

namespace Kongono\Kernel\Contract;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Combines the minimal Kernel and operation lifecycle contracts.
 *
 * This Experimental extension point carries no service, configuration, transport,
 * runtime-adapter, or Application Graph responsibilities.
 */
#[Api(ApiStatus::Experimental)]
interface KernelInterface extends KernelLifecycleInterface, OperationLifecycleInterface {}
