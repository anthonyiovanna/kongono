<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Receipt;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Cost\CostEvidence;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Result\ResultIdentity;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;

/** Carries one bounded receipt claim without replay or authority semantics. */
#[Api(ApiStatus::Experimental)]
final readonly class InvocationReceipt
{
    public function __construct(
        private ReceiptIdentity $identity,
        private InvocationIdentity $invocationIdentity,
        private ModelProfileReference $profile,
        private InvocationOutcome $outcome,
        private int $attemptCount,
        private ?ResultIdentity $resultIdentity,
        private ?UsageEvidence $usage,
        private ?CostEvidence $cost,
        private bool $cleanupComplete,
    ) {
        if ($attemptCount < 0 || $attemptCount > 16) {
            throw InvalidModelInvocationContractException::invalidReceipt('attempt_count_range');
        }
        if (InvocationOutcome::Rejected === $outcome && 0 !== $attemptCount) {
            throw InvalidModelInvocationContractException::invalidReceipt('rejected_attempt_relation');
        }
        if (InvocationOutcome::Rejected !== $outcome && $attemptCount < 1) {
            throw InvalidModelInvocationContractException::invalidReceipt('outcome_attempt_relation');
        }
        if ((InvocationOutcome::Succeeded === $outcome) !== (null !== $resultIdentity)) {
            throw InvalidModelInvocationContractException::invalidReceipt('result_outcome_relation');
        }
        if (null !== $usage && !$profile->capabilities()->supports(ProviderCapability::UsageEvidence)) {
            throw InvalidModelInvocationContractException::invalidReceipt('usage_capability_required');
        }
        if (null !== $cost && !$profile->capabilities()->supports(ProviderCapability::CostEvidence)) {
            throw InvalidModelInvocationContractException::invalidReceipt('cost_capability_required');
        }
    }

    public function identity(): ReceiptIdentity
    {
        return $this->identity;
    }

    public function invocationIdentity(): InvocationIdentity
    {
        return $this->invocationIdentity;
    }

    public function profile(): ModelProfileReference
    {
        return $this->profile;
    }

    public function outcome(): InvocationOutcome
    {
        return $this->outcome;
    }

    public function attemptCount(): int
    {
        return $this->attemptCount;
    }

    public function resultIdentity(): ?ResultIdentity
    {
        return $this->resultIdentity;
    }

    public function usage(): ?UsageEvidence
    {
        return $this->usage;
    }

    public function cost(): ?CostEvidence
    {
        return $this->cost;
    }

    public function cleanupComplete(): bool
    {
        return $this->cleanupComplete;
    }

    public function equals(self $other): bool
    {
        return $this->identity->equals($other->identity)
            && $this->invocationIdentity->equals($other->invocationIdentity)
            && $this->profile->equals($other->profile)
            && $this->outcome === $other->outcome
            && $this->attemptCount === $other->attemptCount
            && (null === $this->resultIdentity ? null === $other->resultIdentity : null !== $other->resultIdentity && $this->resultIdentity->equals($other->resultIdentity))
            && (null === $this->usage ? null === $other->usage : null !== $other->usage && $this->usage->equals($other->usage))
            && (null === $this->cost ? null === $other->cost : null !== $other->cost && $this->cost->equals($other->cost))
            && $this->cleanupComplete === $other->cleanupComplete;
    }
}
