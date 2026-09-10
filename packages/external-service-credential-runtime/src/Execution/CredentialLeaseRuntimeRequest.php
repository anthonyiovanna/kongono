<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Execution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseRequest;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseSource;
use Kongono\ExternalServiceContract\Credential\CredentialMaterialConsumer;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceCredentialRuntime\Clock\CredentialMonotonicClock;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;

/** Binds one explicit source, request, consumer, and clock for one operation. */
#[Api(ApiStatus::Experimental)]
final readonly class CredentialLeaseRuntimeRequest
{
    public function __construct(
        private CredentialLeaseRequest $leaseRequest,
        private CredentialSourceIdentity $sourceIdentity,
        private CredentialLeaseSource $source,
        private CredentialMaterialConsumer $consumer,
        private CredentialMonotonicClock $clock,
    ) {
        if (!$sourceIdentity->equals($leaseRequest->reference()->source())) {
            throw InvalidCredentialLeaseRuntimeException::invalid('source_identity_relation');
        }
    }

    public function leaseRequest(): CredentialLeaseRequest
    {
        return $this->leaseRequest;
    }

    public function sourceIdentity(): CredentialSourceIdentity
    {
        return $this->sourceIdentity;
    }

    public function source(): CredentialLeaseSource
    {
        return $this->source;
    }

    public function consumer(): CredentialMaterialConsumer
    {
        return $this->consumer;
    }

    public function clock(): CredentialMonotonicClock
    {
        return $this->clock;
    }
}
