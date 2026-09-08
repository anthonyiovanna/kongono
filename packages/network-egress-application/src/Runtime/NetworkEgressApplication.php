<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Runtime;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Exception\InvalidNetworkEgressApplicationProfileException;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressApplicationLifecycleException;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressApplicationOperationException;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactValidator;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeRequest;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use Kongono\NetworkEgressRuntime\Runtime\NetworkEgressRuntime;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use Throwable;

/** Owns one exact profile and explicit ports for one terminal synchronous run. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressApplication
{
    private NetworkEgressApplicationState $state = NetworkEgressApplicationState::Ready;
    private ?EgressResolver $resolver;
    private ?EgressTransport $transport;

    /** @internal Constructed only through exact generated wiring. */
    public function __construct(private readonly NetworkEgressApplicationProfile $profile, EgressResolver $resolver, EgressTransport $transport)
    {
        try {
            $current = (new EgressPolicyArtifactValidator())->validateCurrent($profile->source(), $profile->artifact()->canonicalBytes());
            if (!$current->policyIdentity()->equals($profile->source()->identity())) {
                throw InvalidNetworkEgressApplicationProfileException::invalid('artifact_identity_mismatch');
            }
        } catch (InvalidNetworkEgressApplicationProfileException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw InvalidNetworkEgressApplicationProfileException::invalid('artifact_mismatch', $failure);
        }
        try {
            $capabilities = $transport->capabilities();
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw NetworkEgressApplicationLifecycleException::failed('transport_capabilities', $failure);
        }
        foreach ($profile->source()->requiredCapabilities()->all() as $required) {
            if (!$capabilities->contains($required)) {
                throw NetworkEgressApplicationLifecycleException::failed('transport_capability');
            }
        }
        $this->resolver = $resolver;
        $this->transport = $transport;
    }

    public function profile(): NetworkEgressApplicationProfile
    {
        return $this->profile;
    }

    public function state(): NetworkEgressApplicationState
    {
        return $this->state;
    }

    public function hasActiveOperation(): bool
    {
        return NetworkEgressApplicationState::Active === $this->state;
    }

    public function run(NetworkEgressApplicationRunInput $input): EgressRuntimeResult
    {
        if (NetworkEgressApplicationState::Active === $this->state) {
            throw NetworkEgressApplicationLifecycleException::failed('operation_active');
        }
        if (NetworkEgressApplicationState::Retired === $this->state) {
            throw NetworkEgressApplicationLifecycleException::failed('application_retired');
        }
        if (!$this->resolver instanceof EgressResolver || !$this->transport instanceof EgressTransport) {
            $this->state = NetworkEgressApplicationState::Retired;
            throw NetworkEgressApplicationOperationException::invariant();
        }

        $resolver = $this->resolver;
        $transport = $this->transport;
        $this->resolver = null;
        $this->transport = null;
        $this->state = NetworkEgressApplicationState::Active;
        try {
            try {
                $request = new EgressRuntimeRequest(
                    $input->operation(),
                    $this->profile->source(),
                    $this->profile->artifact()->canonicalBytes(),
                    $input->receiptIdentity(),
                    $input->attemptIdentity(),
                    $input->authorityCurrentness(),
                    $resolver,
                    $transport,
                    $input->cancellation(),
                    $input->clock(),
                );
            } catch (Throwable $failure) {
                if ($failure instanceof Error) {
                    throw $failure;
                }
                throw NetworkEgressApplicationOperationException::request($failure);
            }
            try {
                return (new NetworkEgressRuntime())->run($request);
            } catch (Throwable $failure) {
                if ($failure instanceof Error) {
                    throw $failure;
                }
                throw NetworkEgressApplicationOperationException::operation($failure);
            }
        } finally {
            $this->state = NetworkEgressApplicationState::Retired;
            unset($request, $resolver, $transport);
        }
    }
}
