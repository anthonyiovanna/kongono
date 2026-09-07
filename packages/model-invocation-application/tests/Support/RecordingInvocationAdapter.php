<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Tests\Support;

use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterSession;
use Throwable;

final class RecordingInvocationAdapter implements InvocationAdapter
{
    public int $identityCalls = 0;
    public int $openCalls = 0;

    public function __construct(private AdapterIdentity $identity, private RecordingInvocationAdapterSession $session, private ?Throwable $identityFailure = null) {}

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

        return $this->session;
    }
}
