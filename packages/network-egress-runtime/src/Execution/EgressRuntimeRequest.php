<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Execution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Operation\EgressOperation;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Kongono\NetworkEgressRuntime\Authority\EgressAuthorityCurrentness;
use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;

/** Binds one operation, exact policy evidence, and explicit collaborators. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressRuntimeRequest
{
    private string $policyArtifactBytes;

    public function __construct(
        private EgressOperation $operation,
        private EgressPolicySource $policySource,
        string $policyArtifactBytes,
        private EgressReceiptIdentity $receiptIdentity,
        private EgressAttemptIdentity $attemptIdentity,
        private EgressAuthorityCurrentness $authorityCurrentness,
        private EgressResolver $resolver,
        private EgressTransport $transport,
        private EgressCancellation $cancellation,
        private EgressMonotonicClock $clock,
    ) {
        $this->policyArtifactBytes = '' . $policyArtifactBytes;
    }

    public function operation(): EgressOperation
    {
        return $this->operation;
    }
    public function policySource(): EgressPolicySource
    {
        return $this->policySource;
    }
    public function policyArtifactBytes(): string
    {
        return $this->policyArtifactBytes;
    }
    public function receiptIdentity(): EgressReceiptIdentity
    {
        return $this->receiptIdentity;
    }
    public function attemptIdentity(): EgressAttemptIdentity
    {
        return $this->attemptIdentity;
    }
    public function authorityCurrentness(): EgressAuthorityCurrentness
    {
        return $this->authorityCurrentness;
    }
    public function resolver(): EgressResolver
    {
        return $this->resolver;
    }
    public function transport(): EgressTransport
    {
        return $this->transport;
    }
    public function cancellation(): EgressCancellation
    {
        return $this->cancellation;
    }
    public function clock(): EgressMonotonicClock
    {
        return $this->clock;
    }
}
