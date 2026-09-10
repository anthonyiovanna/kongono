<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;

/** Tracks only one operation's closed states and binary attempt counts. */
#[Api(ApiStatus::Internal)]
final class LeaseLifecycle
{
    private CredentialLeaseRuntimeState $state = CredentialLeaseRuntimeState::Prepared;
    private int $acquisitionAttemptCount = 0;
    private int $materialAccessAttemptCount = 0;
    private int $retirementAttemptCount = 0;

    public function transition(CredentialLeaseRuntimeState $state): void
    {
        $this->state = $state;
    }

    public function attemptAcquisition(): void
    {
        $this->acquisitionAttemptCount = $this->once($this->acquisitionAttemptCount);
    }

    public function attemptMaterialAccess(): void
    {
        $this->materialAccessAttemptCount = $this->once($this->materialAccessAttemptCount);
    }

    public function attemptRetirement(): void
    {
        $this->retirementAttemptCount = $this->once($this->retirementAttemptCount);
    }

    public function state(): CredentialLeaseRuntimeState
    {
        return $this->state;
    }

    public function acquisitionAttemptCount(): int
    {
        return $this->acquisitionAttemptCount;
    }

    public function materialAccessAttemptCount(): int
    {
        return $this->materialAccessAttemptCount;
    }

    public function retirementAttemptCount(): int
    {
        return $this->retirementAttemptCount;
    }

    private function once(int $count): int
    {
        if (0 !== $count) {
            throw InvalidCredentialLeaseRuntimeException::invalid('terminal_result_relation');
        }

        return 1;
    }
}
