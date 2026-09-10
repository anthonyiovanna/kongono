<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationOperationException;
use Kongono\ExternalServiceApplication\Operation\ExternalServiceRequestFactory;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Credential\CredentialMaterialConsumer;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplication;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplicationRunInput;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Operation\EgressOperation;
use Kongono\NetworkEgressContract\Policy\EgressPolicyReference;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Kongono\NetworkEgressRuntime\Authority\EgressAuthorityCurrentness;
use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Throwable;

/** Bridges one borrowed material call to one validated Phase 15 operation. */
#[Api(ApiStatus::Internal)]
final class OperationMaterialConsumer implements CredentialMaterialConsumer
{
    private bool $consumed = false;
    private ?ExternalServiceRequestFactory $requestFactory;
    private ?NetworkEgressApplication $networkApplication;
    private ?EgressRuntimeResult $egressResult = null;

    public function __construct(
        private readonly ServiceBinding $binding,
        private readonly ExternalServiceOperationIdentity $operationIdentity,
        ExternalServiceRequestFactory $requestFactory,
        NetworkEgressApplication $networkApplication,
        private readonly EgressPolicySource $policySource,
        private readonly EgressPolicyReference $policyReference,
        private readonly EgressReceiptIdentity $receiptIdentity,
        private readonly EgressAttemptIdentity $attemptIdentity,
        private readonly EgressAuthorityReference $authorityReference,
        private readonly EgressAuthorityCurrentness $authorityCurrentness,
        private readonly EgressCancellation $cancellation,
        private readonly EgressMonotonicClock $clock,
    ) {
        $this->requestFactory = $requestFactory;
        $this->networkApplication = $networkApplication;
    }

    public function consume(#[\SensitiveParameter] string $material): void
    {
        if ($this->consumed
            || !$this->requestFactory instanceof ExternalServiceRequestFactory
            || !$this->networkApplication instanceof NetworkEgressApplication
        ) {
            throw ExternalServiceApplicationOperationException::invariant('consumer_state');
        }
        $this->consumed = true;
        $factory = $this->requestFactory;
        $networkApplication = $this->networkApplication;
        $this->requestFactory = null;
        $this->networkApplication = null;

        try {
            try {
                $request = $factory->create($this->binding, $this->operationIdentity, $material);
            } catch (Throwable $failure) {
                throw ExternalServiceApplicationOperationException::failed('request_factory', $failure);
            }

            try {
                OperationGuard::validate($this->binding, $request);
                $operation = new EgressOperation(
                    new EgressOperationIdentity($this->operationIdentity->value()),
                    $request,
                    $this->binding->endpoint()->origin(),
                    $this->authorityReference,
                    $this->policyReference,
                    $this->policySource->budget(),
                );
                $runInput = new NetworkEgressApplicationRunInput(
                    $operation,
                    $this->receiptIdentity,
                    $this->attemptIdentity,
                    $this->authorityCurrentness,
                    $this->cancellation,
                    $this->clock,
                );
            } catch (Throwable $failure) {
                throw ExternalServiceApplicationOperationException::failed('request_validation', $failure);
            }

            try {
                $this->egressResult = $networkApplication->run($runInput);
            } catch (Throwable $failure) {
                throw ExternalServiceApplicationOperationException::failed('network_application', $failure);
            }
        } finally {
            unset($request, $operation, $runInput, $factory, $networkApplication);
        }
    }

    public function consumed(): bool
    {
        return $this->consumed;
    }

    public function egressResult(): ?EgressRuntimeResult
    {
        return $this->egressResult;
    }
}
