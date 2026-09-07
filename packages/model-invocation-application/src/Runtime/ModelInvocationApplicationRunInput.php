<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;

/** Carries only the three remaining explicit Phase 14D operation collaborators. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationApplicationRunInput
{
    public function __construct(
        private ReceiptIdentity $receiptIdentity,
        private InvocationCancellation $cancellation,
        private InvocationMonotonicClock $clock,
    ) {}

    public function receiptIdentity(): ReceiptIdentity
    {
        return $this->receiptIdentity;
    }
    public function cancellation(): InvocationCancellation
    {
        return $this->cancellation;
    }
    public function clock(): InvocationMonotonicClock
    {
        return $this->clock;
    }
}
