<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Execution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceCredentialRuntime\Clock\CredentialMonotonicClock;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesExternalServiceApplicationFactory;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressRuntime\Authority\EgressAuthorityCurrentness;
use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;

/** Carries every explicit application-owned input for one provider operation. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesInvocationContext
{
    public function __construct(
        private InvocationPlanIdentity $expectedPlanIdentity,
        private OpenAiResponsesExternalServiceApplicationFactory $applicationFactory,
        private ExternalServiceOperationIdentity $operationIdentity,
        private CredentialPurpose $credentialPurpose,
        private int $credentialMaximumLifetimeMilliseconds,
        private EgressReceiptIdentity $receiptIdentity,
        private EgressAttemptIdentity $attemptIdentity,
        private EgressAuthorityReference $authorityReference,
        private EgressAuthorityCurrentness $authorityCurrentness,
        private EgressCancellation $cancellation,
        private CredentialMonotonicClock $credentialClock,
        private EgressMonotonicClock $egressClock,
    ) {
        if ($credentialMaximumLifetimeMilliseconds < 1 || $credentialMaximumLifetimeMilliseconds > 300_000) {
            throw OpenAiResponsesInvocationException::invalidContext('credential_lifetime');
        }
    }

    public function expectedPlanIdentity(): InvocationPlanIdentity
    {
        return $this->expectedPlanIdentity;
    }
    public function applicationFactory(): OpenAiResponsesExternalServiceApplicationFactory
    {
        return $this->applicationFactory;
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
