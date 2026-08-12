<?php

declare(strict_types=1);

namespace Kongono\Kernel\Contract;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Kernel\Lifecycle\OperationScope;

/**
 * Defines explicit ownership of overlapping operation lifetimes.
 *
 * A returned scope is an opaque identity token owned by its creating Kernel.
 * Callers must return that exact active object to the same Kernel. The contract
 * deliberately provides no global current operation or self-ending scope.
 * Stability: Experimental.
 */
#[Api(ApiStatus::Experimental)]
interface OperationLifecycleInterface
{
    /**
     * Creates and owns a distinct operation scope while the Kernel is booted.
     *
     * @throws LifecycleException if the Kernel is not booted
     */
    public function beginOperation(): OperationScope;

    /**
     * Completes an active scope owned by this booted Kernel.
     *
     * @throws LifecycleException if the Kernel is not booted or does not own the active scope
     */
    public function endOperation(OperationScope $operation): void;

    /** Returns the exact number of scopes currently active in this Kernel. */
    public function activeOperationCount(): int;
}
