<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceCredentialRuntime\Clock\CredentialMonotonicClock;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesExternalServiceApplicationFactory;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressRuntime\Authority\EgressAuthorityCurrentness;
use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;

/** Carries every explicit collaborator for one provider application run. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesApplicationRunInput
{
    public function __construct(
        private OpenAiResponsesExternalServiceApplicationFactory $applicationFactory,
        private ExternalServiceOperationIdentity $externalServiceOperationIdentity,
        private CredentialPurpose $credentialPurpose,
        private int $credentialMaximumLifetimeMilliseconds,
        private EgressReceiptIdentity $egressReceiptIdentity,
        private EgressAttemptIdentity $egressAttemptIdentity,
        private EgressAuthorityReference $egressAuthorityReference,
        private EgressAuthorityCurrentness $egressAuthorityCurrentness,
        private EgressCancellation $egressCancellation,
        private EgressMonotonicClock $egressClock,
        private CredentialMonotonicClock $credentialClock,
        private ReceiptIdentity $invocationReceiptIdentity,
        private InvocationCancellation $invocationCancellation,
        private InvocationMonotonicClock $invocationClock,
    ) {}

    public function applicationFactory(): OpenAiResponsesExternalServiceApplicationFactory
    {
        return $this->applicationFactory;
    }
    public function externalServiceOperationIdentity(): ExternalServiceOperationIdentity
    {
        return $this->externalServiceOperationIdentity;
    }
    public function credentialPurpose(): CredentialPurpose
    {
        return $this->credentialPurpose;
    }
    public function credentialMaximumLifetimeMilliseconds(): int
    {
        return $this->credentialMaximumLifetimeMilliseconds;
    }
    public function egressReceiptIdentity(): EgressReceiptIdentity
    {
        return $this->egressReceiptIdentity;
    }
    public function egressAttemptIdentity(): EgressAttemptIdentity
    {
        return $this->egressAttemptIdentity;
    }
    public function egressAuthorityReference(): EgressAuthorityReference
    {
        return $this->egressAuthorityReference;
    }
    public function egressAuthorityCurrentness(): EgressAuthorityCurrentness
    {
        return $this->egressAuthorityCurrentness;
    }
    public function egressCancellation(): EgressCancellation
    {
        return $this->egressCancellation;
    }
    public function egressClock(): EgressMonotonicClock
    {
        return $this->egressClock;
    }
    public function credentialClock(): CredentialMonotonicClock
    {
        return $this->credentialClock;
    }
    public function invocationReceiptIdentity(): ReceiptIdentity
    {
        return $this->invocationReceiptIdentity;
    }
    public function invocationCancellation(): InvocationCancellation
    {
        return $this->invocationCancellation;
    }
    public function invocationClock(): InvocationMonotonicClock
    {
        return $this->invocationClock;
    }
}
