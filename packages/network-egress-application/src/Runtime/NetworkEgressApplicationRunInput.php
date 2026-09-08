<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Operation\EgressOperation;
use Kongono\NetworkEgressRuntime\Authority\EgressAuthorityCurrentness;
use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;

/** Carries exactly the six explicit Phase 15D operation inputs. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressApplicationRunInput
{
    public function __construct(
        private EgressOperation $operation,
        private EgressReceiptIdentity $receiptIdentity,
        private EgressAttemptIdentity $attemptIdentity,
        private EgressAuthorityCurrentness $authorityCurrentness,
        private EgressCancellation $cancellation,
        private EgressMonotonicClock $clock,
    ) {}

    public function operation(): EgressOperation
    {
        return $this->operation;
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

    public function cancellation(): EgressCancellation
    {
        return $this->cancellation;
    }

    public function clock(): EgressMonotonicClock
    {
        return $this->clock;
    }
}
