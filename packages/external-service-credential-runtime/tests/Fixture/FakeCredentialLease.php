<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Fixture;

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

    /**
     * @param array<string, list<Throwable>> $accessorFailures
     * @param null|Closure(string): void $observer
     */
    public function __construct(
        private CredentialLeaseIdentity $identity,
        private CredentialReference $reference,
        private CredentialPurpose $purpose,
        private CredentialLeaseState $state,
        private string $borrowedValue,
        private ?Throwable $materialFailure = null,
        private ?Throwable $retirementFailure = null,
        private CredentialLeaseState $postMaterialState = CredentialLeaseState::Acquired,
        private CredentialLeaseState $postRetirementState = CredentialLeaseState::Retired,
        private ?Closure $duringMaterial = null,
        private array $accessorFailures = [],
        private ?Closure $observer = null,
    ) {}

    public function identity(): CredentialLeaseIdentity
    {
        $this->observe('lease.identity');
        $this->failAccessor('identity');

        return $this->identity;
    }

    public function reference(): CredentialReference
    {
        $this->observe('lease.reference');
        $this->failAccessor('reference');

        return $this->reference;
    }

    public function purpose(): CredentialPurpose
    {
        $this->observe('lease.purpose');
        $this->failAccessor('purpose');

        return $this->purpose;
    }

    public function state(): CredentialLeaseState
    {
        $this->observe('lease.state');
        $this->failAccessor('state');

        return $this->state;
    }

    public function withMaterial(CredentialMaterialConsumer $consumer): void
    {
        ++$this->materialCalls;
        $this->observe('lease.with_material');
        $this->state = CredentialLeaseState::InUse;
        if (null !== $this->duringMaterial) {
            ($this->duringMaterial)();
        }
        if (null !== $this->materialFailure) {
            throw $this->materialFailure;
        }
        $consumer->consume($this->borrowedValue);
        $this->state = $this->postMaterialState;
    }

    public function retire(): void
    {
        ++$this->retireCalls;
        $this->observe('lease.retire');
        $this->state = CredentialLeaseState::Retiring;
        if (null !== $this->retirementFailure) {
            throw $this->retirementFailure;
        }
        $this->state = $this->postRetirementState;
    }

    private function failAccessor(string $accessor): void
    {
        if ([] === ($this->accessorFailures[$accessor] ?? [])) {
            return;
        }
        $failure = array_shift($this->accessorFailures[$accessor]);
        throw $failure;
    }

    private function observe(string $event): void
    {
        if (null !== $this->observer) {
            ($this->observer)($event);
        }
    }
}
