<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Plan;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;

/** Binds one complete explicit request, adapter label, and finite inert budget. */
#[Api(ApiStatus::Experimental)]
final readonly class InvocationPlanSource
{
    public function __construct(
        private InvocationPlanIdentity $identity,
        private AdapterIdentity $adapterIdentity,
        private InvocationRequest $request,
        private InvocationExecutionBudget $budget,
    ) {}

    public function identity(): InvocationPlanIdentity
    {
        return $this->identity;
    }

    public function adapterIdentity(): AdapterIdentity
    {
        return $this->adapterIdentity;
    }

    public function request(): InvocationRequest
    {
        return $this->request;
    }

    public function budget(): InvocationExecutionBudget
    {
        return $this->budget;
    }

    public function equals(self $other): bool
    {
        return $this->identity->equals($other->identity)
            && $this->adapterIdentity->equals($other->adapterIdentity)
            && $this->request->equals($other->request)
            && $this->budget->equals($other->budget);
    }
}
