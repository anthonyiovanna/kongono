<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Execution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;

/** Binds exact caller-owned plan evidence and operation collaborators. */
#[Api(ApiStatus::Experimental)]
final readonly class InvocationRuntimeRequest
{
    private string $artifactBytes;

    public function __construct(
        private InvocationPlanSource $source,
        string $artifactBytes,
        private ReceiptIdentity $receiptIdentity,
        private InvocationAdapter $adapter,
        private InvocationCancellation $cancellation,
        private InvocationMonotonicClock $clock,
    ) {
        $this->artifactBytes = '' . $artifactBytes;
    }

    public function source(): InvocationPlanSource
    {
        return $this->source;
    }

    public function artifactBytes(): string
    {
        return $this->artifactBytes;
    }

    public function receiptIdentity(): ReceiptIdentity
    {
        return $this->receiptIdentity;
    }

    public function adapter(): InvocationAdapter
    {
        return $this->adapter;
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
