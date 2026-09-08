<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Cancellation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;

/** Stores retrospective cancellation evidence without cancelling work. */
#[Api(ApiStatus::Experimental)]
final readonly class CancellationEvidence
{
    public function __construct(
        private CancellationState $state,
        private int $observationSequence,
    ) {
        if ($observationSequence > 1000000) {
            throw InvalidNetworkEgressContractException::contractLimitExceeded('cancellation_observation_sequence', 1000000);
        }
        if ($observationSequence < 0) {
            throw InvalidNetworkEgressContractException::invalidOutcomeOrReceipt('cancellation_sequence_range');
        }
    }

    public function state(): CancellationState
    {
        return $this->state;
    }

    public function observationSequence(): int
    {
        return $this->observationSequence;
    }

    public function equals(self $other): bool
    {
        return $this->state === $other->state && $this->observationSequence === $other->observationSequence;
    }
}
