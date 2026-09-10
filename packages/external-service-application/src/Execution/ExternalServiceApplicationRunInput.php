<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Execution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationOperationException;
use Kongono\ExternalServiceApplication\Operation\ExternalServiceRequestFactory;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceCredentialRuntime\Clock\CredentialMonotonicClock;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressRuntime\Authority\EgressAuthorityCurrentness;
use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;

/** Carries exactly the explicit inputs for one credential-backed egress operation. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceApplicationRunInput
{
    public function __construct(
        private ExternalServiceOperationIdentity $operationIdentity,
        private CredentialPurpose $credentialPurpose,
        private int $credentialMaximumLifetimeMilliseconds,
        private ExternalServiceRequestFactory $requestFactory,
        private EgressReceiptIdentity $receiptIdentity,
        private EgressAttemptIdentity $attemptIdentity,
        private EgressAuthorityReference $authorityReference,
        private EgressAuthorityCurrentness $authorityCurrentness,
        private EgressCancellation $cancellation,
        private CredentialMonotonicClock $credentialClock,
        private EgressMonotonicClock $egressClock,
    ) {
        if ($credentialMaximumLifetimeMilliseconds < 1 || $credentialMaximumLifetimeMilliseconds > 300000) {
            throw ExternalServiceApplicationOperationException::invariant('run_input');
        }
    }

    public function operationIdentity(): ExternalServiceOperationIdentity
    {
        return $this->operationIdentity;
    }

    public function credentialPurpose(): CredentialPurpose
    {
        return $this->credentialPurpose;
    }

    public function credentialMaximumLifetimeMilliseconds(): int
    {
        return $this->credentialMaximumLifetimeMilliseconds;
    }

    public function requestFactory(): ExternalServiceRequestFactory
    {
        return $this->requestFactory;
    }

    public function receiptIdentity(): EgressReceiptIdentity
    {
        return $this->receiptIdentity;
    }

    public function attemptIdentity(): EgressAttemptIdentity
    {
        return $this->attemptIdentity;
    }

    public function authorityReference(): EgressAuthorityReference
    {
        return $this->authorityReference;
    }

    public function authorityCurrentness(): EgressAuthorityCurrentness
    {
        return $this->authorityCurrentness;
    }

    public function cancellation(): EgressCancellation
    {
        return $this->cancellation;
    }

    public function credentialClock(): CredentialMonotonicClock
    {
        return $this->credentialClock;
    }

    public function egressClock(): EgressMonotonicClock
    {
        return $this->egressClock;
    }
}
