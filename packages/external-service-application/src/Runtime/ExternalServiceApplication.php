<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationLifecycleException;
use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationOperationException;
use Kongono\ExternalServiceApplication\Exception\InvalidExternalServiceApplicationProfileException;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationResult;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationRunInput;
use Kongono\ExternalServiceApplication\Internal\OperationMaterialConsumer;
use Kongono\ExternalServiceApplication\Internal\ResultFactory;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileFactory;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseRequest;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseSource;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Resolution\EndpointResolver;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeRequest;
use Kongono\ExternalServiceCredentialRuntime\Runtime\CredentialLeaseRuntime;
use Kongono\NetworkEgressApplication\Runtime\LoadedNetworkEgressWiring;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplication;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use Throwable;

/** Composes one explicit credential lease with one exact Phase 15 application run. */
#[Api(ApiStatus::Experimental)]
final class ExternalServiceApplication
{
    private ExternalServiceApplicationState $state = ExternalServiceApplicationState::Ready;
    private ?NetworkEgressApplication $networkApplication = null;
    private ?CredentialSourceIdentity $credentialSourceIdentity = null;
    private ?CredentialLeaseSource $credentialSource = null;

    public function __construct(
        private readonly ExternalServiceApplicationProfile $profile,
        LoadedNetworkEgressWiring $loadedWiring,
        EndpointResolver $resolver,
        EgressTransport $transport,
        CredentialSourceIdentity $credentialSourceIdentity,
        CredentialLeaseSource $credentialSource,
    ) {
        try {
            $current = (new ExternalServiceApplicationProfileFactory())->create(
                $profile->bindingSource(),
                $profile->bindingArtifact()->canonicalBytes(),
                $profile->binding()->identity(),
                $profile->networkEgressProfile(),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof InvalidExternalServiceApplicationProfileException) {
                throw $failure;
            }
            throw InvalidExternalServiceApplicationProfileException::invalid('binding_artifact_mismatch', $failure);
        }
        if (!$current->identity()->equals($profile->identity())) {
            throw InvalidExternalServiceApplicationProfileException::invalid('network_profile_mismatch');
        }
        if (!$credentialSourceIdentity->equals($profile->binding()->credentialReference()->source())) {
            throw InvalidExternalServiceApplicationProfileException::invalid('credential_source_identity');
        }

        try {
            $networkApplication = $loadedWiring->start($resolver, $transport);
        } catch (Throwable $failure) {
            throw ExternalServiceApplicationLifecycleException::failed('network_application_start', $failure);
        }
        if (!$networkApplication->profile()->identity()->equals($profile->networkEgressProfile()->identity())) {
            throw InvalidExternalServiceApplicationProfileException::invalid('loaded_profile_mismatch');
        }

        $this->networkApplication = $networkApplication;
        $this->credentialSourceIdentity = $credentialSourceIdentity;
        $this->credentialSource = $credentialSource;
    }

    public function profile(): ExternalServiceApplicationProfile
    {
        return $this->profile;
    }

    public function state(): ExternalServiceApplicationState
    {
        return $this->state;
    }

    public function hasActiveOperation(): bool
    {
        return ExternalServiceApplicationState::Active === $this->state;
    }

    public function run(ExternalServiceApplicationRunInput $input): ExternalServiceApplicationResult
    {
        if (ExternalServiceApplicationState::Active === $this->state) {
            throw ExternalServiceApplicationLifecycleException::failed('operation_active');
        }
        if (ExternalServiceApplicationState::Retired === $this->state) {
            throw ExternalServiceApplicationLifecycleException::failed('application_retired');
        }
        if (!$this->networkApplication instanceof NetworkEgressApplication
            || !$this->credentialSourceIdentity instanceof CredentialSourceIdentity
            || !$this->credentialSource instanceof CredentialLeaseSource
        ) {
            $this->state = ExternalServiceApplicationState::Retired;
            throw ExternalServiceApplicationOperationException::invariant('collaborator_state');
        }

        $networkApplication = $this->networkApplication;
        $credentialSourceIdentity = $this->credentialSourceIdentity;
        $credentialSource = $this->credentialSource;
        $this->networkApplication = null;
        $this->credentialSourceIdentity = null;
        $this->credentialSource = null;
        $this->state = ExternalServiceApplicationState::Active;

        try {
            $consumer = new OperationMaterialConsumer(
                $this->profile->binding(),
                $input->operationIdentity(),
                $input->requestFactory(),
                $networkApplication,
                $this->profile->networkEgressProfile()->source(),
                $this->profile->networkEgressProfile()->artifact()->policyReference(),
                $input->receiptIdentity(),
                $input->attemptIdentity(),
                $input->authorityReference(),
                $input->authorityCurrentness(),
                $input->cancellation(),
                $input->egressClock(),
            );
            try {
                $leaseRequest = new CredentialLeaseRequest(
                    $input->operationIdentity(),
                    $this->profile->binding()->credentialReference(),
                    $input->credentialPurpose(),
                    $input->credentialMaximumLifetimeMilliseconds(),
                );
                $credentialRequest = new CredentialLeaseRuntimeRequest(
                    $leaseRequest,
                    $credentialSourceIdentity,
                    $credentialSource,
                    $consumer,
                    $input->credentialClock(),
                );
                $credentialResult = (new CredentialLeaseRuntime())->run($credentialRequest);
            } catch (Throwable $failure) {
                throw ExternalServiceApplicationOperationException::failed('credential_runtime', $failure, $consumer->egressResult());
            }

            $egressResult = $consumer->egressResult();
            if (!$consumer->consumed() || null === $egressResult) {
                throw ExternalServiceApplicationOperationException::invariant('consumer_state');
            }
            try {
                return ResultFactory::create($this->profile, $input, $credentialResult, $egressResult);
            } catch (Throwable $failure) {
                if ($failure instanceof ExternalServiceApplicationOperationException) {
                    throw $failure;
                }
                throw ExternalServiceApplicationOperationException::failed('result_mapping', $failure, $egressResult);
            }
        } finally {
            $this->state = ExternalServiceApplicationState::Retired;
            $this->networkApplication = null;
            $this->credentialSourceIdentity = null;
            $this->credentialSource = null;
            unset($networkApplication, $credentialSourceIdentity, $credentialSource, $consumer, $leaseRequest, $credentialRequest, $credentialResult, $egressResult);
        }
    }
}
