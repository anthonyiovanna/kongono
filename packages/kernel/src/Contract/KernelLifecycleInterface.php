<?php

declare(strict_types=1);

namespace Kongono\Kernel\Contract;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Kernel\Lifecycle\KernelState;

/**
 * Defines the terminal boot and shutdown transitions for one Kernel lifetime.
 *
 * Implementations are expected to be persistent-runtime-safe: boot occurs once,
 * shutdown is terminal, and invalid transitions fail without partial mutation.
 * The contract performs no transport, container, runtime-adapter, or cleanup work.
 * Stability: Experimental.
 */
#[Api(ApiStatus::Experimental)]
interface KernelLifecycleInterface
{
    /** Returns the current lifecycle state without changing it. */
    public function state(): KernelState;

    /**
     * Transitions an unbooted Kernel to booted.
     *
     * @throws LifecycleException if the Kernel is not unbooted
     */
    public function boot(): void;

    /**
     * Transitions a booted Kernel with no active operations to shutdown.
     *
     * @throws LifecycleException if the Kernel is not booted or work remains active
     */
    public function shutdown(): void;
}
