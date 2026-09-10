<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Fixture;

use Closure;
use Kongono\ExternalServiceContract\Credential\CredentialLease;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseState;
use Kongono\ExternalServiceContract\Credential\CredentialMaterialConsumer;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Throwable;

final class FakeCredentialLease implements CredentialLease
{
    public int $materialCalls = 0;
    public int $retireCalls = 0;

    public function __construct(
        private CredentialReference $reference,
        private CredentialPurpose $purpose,
        private string $material,
        private ?Closure $observer = null,
        private ?Throwable $materialFailure = null,
        private ?Throwable $retirementFailure = null,
        private CredentialLeaseState $state = CredentialLeaseState::Acquired,
    ) {}

    public function identity(): CredentialLeaseIdentity
    {
        return new CredentialLeaseIdentity('lease.external-service-application');
    }

    public function reference(): CredentialReference
    {
        return $this->reference;
    }

    public function purpose(): CredentialPurpose
    {
        return $this->purpose;
    }

    public function state(): CredentialLeaseState
    {
        return $this->state;
    }

    public function withMaterial(CredentialMaterialConsumer $consumer): void
    {
        ++$this->materialCalls;
        $this->state = CredentialLeaseState::InUse;
        if (null !== $this->observer) {
            ($this->observer)('lease.with_material');
        }
        if ($this->materialFailure instanceof Throwable) {
            throw $this->materialFailure;
        }
        $consumer->consume($this->material);
        $this->state = CredentialLeaseState::Acquired;
    }

    public function retire(): void
    {
        ++$this->retireCalls;
        $this->state = CredentialLeaseState::Retiring;
        if (null !== $this->observer) {
            ($this->observer)('lease.retire');
        }
        if ($this->retirementFailure instanceof Throwable) {
            throw $this->retirementFailure;
        }
        $this->state = CredentialLeaseState::Retired;
    }
}
