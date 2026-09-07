<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Fixture;

use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterSession;
use Throwable;

final class FakeInvocationAdapter implements InvocationAdapter
{
    public int $identityCalls = 0;
    public int $openCalls = 0;

    public function __construct(
        private AdapterIdentity $identity,
        private FakeInvocationAdapterSession $session,
        private ?Throwable $openFailure = null,
        private ?Throwable $identityFailure = null,
    ) {}

    public function identity(): AdapterIdentity
    {
        ++$this->identityCalls;
        if (null !== $this->identityFailure) {
            throw $this->identityFailure;
        }

        return $this->identity;
    }

    public function open(InvocationPlanSource $source): InvocationAdapterSession
    {
        ++$this->openCalls;
        if (null !== $this->openFailure) {
            throw $this->openFailure;
        }

        return $this->session;
    }
}
