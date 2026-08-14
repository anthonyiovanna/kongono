<?php

declare(strict_types=1);

namespace Kongono\Kernel;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Kernel\Contract\KernelInterface;
use Kongono\Kernel\Diagnostic\LifecycleDiagnostic;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Kernel\Lifecycle\KernelState;
use Kongono\Kernel\Lifecycle\OperationScope;
use SplObjectStorage;

/**
 * Owns Kongono's minimal runtime-neutral lifecycle state machine.
 *
 * One instance boots once, owns any number of overlapping operation scopes by
 * object identity, and shuts down only after all scopes end. All state is local to
 * the instance; no static, ambient, thread-local, or fiber-local current operation
 * exists. Calls perform no filesystem, network, environment, time, randomness,
 * logging, telemetry, process, or other global-state access.
 *
 * Invalid transitions throw {@see LifecycleException} before mutation. The class
 * is final and Experimental: consumers may evaluate it but must not treat its API
 * as stable or extend it.
 */
#[Api(ApiStatus::Experimental)]
final class Kernel implements KernelInterface
{
    private KernelState $state = KernelState::Unbooted;

    /** @var SplObjectStorage<OperationScope, null> */
    private SplObjectStorage $activeOperations;

    public function __construct()
    {
        $this->activeOperations = new SplObjectStorage();
    }

    public function state(): KernelState
    {
        return $this->state;
    }

    public function boot(): void
    {
        if (KernelState::Unbooted !== $this->state) {
            throw new LifecycleException(
                LifecycleDiagnostic::BootWhenNotUnbooted,
                sprintf('Cannot boot Kernel while in %s state.', $this->state->name),
                [
                    'actual_state' => $this->state->name,
                    'expected_state' => KernelState::Unbooted->name,
                ],
            );
        }

        $this->state = KernelState::Booted;
    }

    public function shutdown(): void
    {
        if (KernelState::Booted !== $this->state) {
            throw new LifecycleException(
                LifecycleDiagnostic::ShutdownWhenNotBooted,
                sprintf('Cannot shut down Kernel while in %s state.', $this->state->name),
                [
                    'actual_state' => $this->state->name,
                    'expected_state' => KernelState::Booted->name,
                ],
            );
        }

        $activeOperationCount = $this->activeOperations->count();

        if (0 !== $activeOperationCount) {
            throw new LifecycleException(
                LifecycleDiagnostic::ShutdownWithActiveOperations,
                sprintf(
                    'Cannot shut down Kernel while %d operation(s) remain active.',
                    $activeOperationCount,
                ),
                [
                    'active_operation_count' => $activeOperationCount,
                    'expected_active_operation_count' => 0,
                ],
            );
        }

        $this->state = KernelState::Shutdown;
    }

    public function beginOperation(): OperationScope
    {
        if (KernelState::Booted !== $this->state) {
            throw new LifecycleException(
                LifecycleDiagnostic::BeginOperationWhenNotBooted,
                sprintf(
                    'Cannot begin an operation while Kernel is in %s state.',
                    $this->state->name,
                ),
                [
                    'actual_state' => $this->state->name,
                    'expected_state' => KernelState::Booted->name,
                ],
            );
        }

        $operation = new OperationScope();
        $this->activeOperations[$operation] = null;

        return $operation;
    }

    public function endOperation(OperationScope $operation): void
    {
        if (KernelState::Booted !== $this->state) {
            throw new LifecycleException(
                LifecycleDiagnostic::EndOperationWhenNotBooted,
                sprintf(
                    'Cannot end an operation while Kernel is in %s state.',
                    $this->state->name,
                ),
                [
                    'actual_state' => $this->state->name,
                    'expected_state' => KernelState::Booted->name,
                ],
            );
        }

        if (!isset($this->activeOperations[$operation])) {
            throw new LifecycleException(
                LifecycleDiagnostic::InactiveOrForeignOperationScope,
                'Cannot end an operation that is not active and owned by this Kernel.',
                ['scope_condition' => 'inactive_or_foreign'],
            );
        }

        unset($this->activeOperations[$operation]);
    }

    public function activeOperationCount(): int
    {
        return $this->activeOperations->count();
    }
}
