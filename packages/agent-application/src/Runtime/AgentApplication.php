<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Runtime;

use Kongono\AgentApplication\Exception\AgentApplicationLifecycleException;
use Kongono\AgentApplication\Exception\AgentApplicationOperationException;
use Kongono\AgentApplication\Internal\LifecycleFailureComposer;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\AgentRuntime\Runtime\AgentRuntime;
use Kongono\AgentRuntime\Runtime\AgentRuntimeRequest;
use Kongono\AgentRuntime\Runtime\AgentRuntimeResult;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Owns one profile and at most one synchronous operation while delegating effects to Agent Runtime. */
#[Api(ApiStatus::Experimental)]
final class AgentApplication
{
    private AgentApplicationState $state = AgentApplicationState::Open;
    private bool $active = false;

    public function __construct(private readonly AgentApplicationProfile $profile) {}

    public function profile(): AgentApplicationProfile
    {
        return $this->profile;
    }

    public function state(): AgentApplicationState
    {
        return $this->state;
    }

    public function hasActiveOperation(): bool
    {
        return $this->active;
    }

    public function run(AgentApplicationRunInput $input): AgentRuntimeResult
    {
        $primary = null;
        if (AgentApplicationState::Closed === $this->state) {
            $primary = AgentApplicationLifecycleException::failed('application_closed');
        } elseif ($this->active) {
            $primary = AgentApplicationLifecycleException::failed('operation_active');
        }
        if (null !== $primary) {
            LifecycleFailureComposer::throwIfNeeded($primary, self::closePorts($input));
        }

        $this->active = true;
        $transferred = false;
        try {
            try {
                $request = new AgentRuntimeRequest(
                    $this->profile->declaration(),
                    $this->profile->authorityPlan(),
                    $input->operation(),
                    $input->intent(),
                    $input->budget(),
                    $input->proposalPort(),
                    $input->capabilityPorts(),
                    $input->authorityCurrentness(),
                    $input->cancellation(),
                    $input->clock(),
                );
            } catch (Throwable $failure) {
                $primary = AgentApplicationOperationException::failed('request', $failure);
                LifecycleFailureComposer::throwIfNeeded($primary, self::closePorts($input));
                throw $primary;
            }
            $transferred = true;

            return (new AgentRuntime())->run($request);
        } catch (Throwable $failure) {
            if ($transferred) {
                throw $failure;
            }
            throw $failure;
        } finally {
            $this->active = false;
        }
    }

    public function close(): void
    {
        if (AgentApplicationState::Closed === $this->state) {
            return;
        }
        if ($this->active) {
            throw AgentApplicationLifecycleException::failed('active_close');
        }
        $this->state = AgentApplicationState::Closed;
    }

    /** @return list<Throwable> */
    private static function closePorts(AgentApplicationRunInput $input): array
    {
        $failures = [];
        $ports = $input->capabilityPorts();
        for ($index = count($ports) - 1; $index >= 0; --$index) {
            try {
                $ports[$index]->close();
            } catch (Throwable $failure) {
                $failures[] = $failure;
            }
        }
        try {
            $input->proposalPort()->close();
        } catch (Throwable $failure) {
            $failures[] = $failure;
        }

        return $failures;
    }
}
