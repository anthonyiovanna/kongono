<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Tests\Unit\Runtime;

use Kongono\ModelInvocationApplication\Exception\InvalidModelInvocationApplicationProfileException;
use Kongono\ModelInvocationApplication\Exception\ModelInvocationApplicationLifecycleException;
use Kongono\ModelInvocationApplication\Runtime\ModelInvocationApplication;
use Kongono\ModelInvocationApplication\Runtime\ModelInvocationApplicationState;
use Kongono\ModelInvocationApplication\Tests\Support\ModelInvocationApplicationFactory;
use Kongono\ModelInvocationApplication\Tests\Support\RecordingCancellation;
use Kongono\ModelInvocationApplication\Tests\Support\RecordingInvocationAdapter;
use Kongono\ModelInvocationApplication\Tests\Support\RecordingInvocationAdapterSession;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterEmission;
use Kongono\ModelInvocationRuntime\Exception\InvocationCleanupException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ModelInvocationApplicationTest extends TestCase
{
    public function testOneRunDelegatesOnceReturnsExactResultEvidenceAndRetires(): void
    {
        $source = ModelInvocationApplicationFactory::source();
        $modelResult = ModelInvocationApplicationFactory::result($source);
        $session = new RecordingInvocationAdapterSession([InvocationAdapterEmission::result($modelResult), null]);
        $adapter = new RecordingInvocationAdapter(new AdapterIdentity('adapter.application'), $session);
        $application = new ModelInvocationApplication(ModelInvocationApplicationFactory::profile($source), $adapter);

        $result = $application->run(ModelInvocationApplicationFactory::runInput());

        self::assertSame($modelResult, $result->result());
        self::assertSame(InvocationState::Retired, $result->state());
        self::assertSame(1, $adapter->openCalls);
        self::assertSame(1, $session->closeCalls);
        self::assertSame(ModelInvocationApplicationState::Retired, $application->state());
        self::assertFalse($application->hasActiveOperation());

        $this->expectException(ModelInvocationApplicationLifecycleException::class);
        $application->run(ModelInvocationApplicationFactory::runInput());
    }

    public function testAdapterIdentityMismatchFailsBeforeOpen(): void
    {
        $adapter = new RecordingInvocationAdapter(new AdapterIdentity('adapter.wrong'), new RecordingInvocationAdapterSession([]));
        try {
            new ModelInvocationApplication(ModelInvocationApplicationFactory::profile(), $adapter);
            self::fail('Mismatched adapter must fail construction.');
        } catch (InvalidModelInvocationApplicationProfileException) {
            self::assertSame(0, $adapter->openCalls);
        }
    }

    public function testCancellationRetiresWithoutOpeningAdapter(): void
    {
        $adapter = new RecordingInvocationAdapter(new AdapterIdentity('adapter.application'), new RecordingInvocationAdapterSession([]));
        $application = new ModelInvocationApplication(ModelInvocationApplicationFactory::profile(), $adapter);
        $result = $application->run(ModelInvocationApplicationFactory::runInput(new RecordingCancellation([true])));

        self::assertSame(InvocationOutcome::Cancelled, $result->receipt()->outcome());
        self::assertSame(0, $adapter->openCalls);
        self::assertSame(ModelInvocationApplicationState::Retired, $application->state());
    }

    public function testReentrancyFailsBeforeSecondAdapterEffect(): void
    {
        $source = ModelInvocationApplicationFactory::source();
        $reentrant = new class {
            public ?ModelInvocationApplication $application = null;
            public ?ModelInvocationApplicationLifecycleException $failure = null;

            public function __invoke(): void
            {
                if (null === $this->application) {
                    return;
                }
                try {
                    $this->application->run(ModelInvocationApplicationFactory::runInput());
                } catch (ModelInvocationApplicationLifecycleException $failure) {
                    $this->failure = $failure;
                }
            }
        };
        $session = new RecordingInvocationAdapterSession(
            [InvocationAdapterEmission::result(ModelInvocationApplicationFactory::result($source)), null],
            onNext: $reentrant(...),
        );
        $adapter = new RecordingInvocationAdapter(new AdapterIdentity('adapter.application'), $session);
        $application = new ModelInvocationApplication(ModelInvocationApplicationFactory::profile($source), $adapter);
        $reentrant->application = $application;
        $application->run(ModelInvocationApplicationFactory::runInput());

        self::assertInstanceOf(ModelInvocationApplicationLifecycleException::class, $reentrant->failure);
        self::assertSame(1, $adapter->openCalls);
    }

    public function testCleanupFailureAndPreviousThrowableRemainLowerLayerExact(): void
    {
        $source = ModelInvocationApplicationFactory::source();
        $cleanup = new RuntimeException('foreign-cleanup-secret');
        $session = new RecordingInvocationAdapterSession([InvocationAdapterEmission::result(ModelInvocationApplicationFactory::result($source)), null], $cleanup);
        $application = new ModelInvocationApplication(ModelInvocationApplicationFactory::profile($source), new RecordingInvocationAdapter(new AdapterIdentity('adapter.application'), $session));

        try {
            $application->run(ModelInvocationApplicationFactory::runInput());
            self::fail('Cleanup failure must remain visible.');
        } catch (InvocationCleanupException $failure) {
            self::assertSame([$cleanup], $failure->cleanupFailures());
            self::assertSame($cleanup, $failure->getPrevious());
        }
        self::assertSame(ModelInvocationApplicationState::Retired, $application->state());
    }

    public function testFreshInstancesDoNotShareLifecycleState(): void
    {
        $source = ModelInvocationApplicationFactory::source();
        $first = new ModelInvocationApplication(ModelInvocationApplicationFactory::profile($source), new RecordingInvocationAdapter(new AdapterIdentity('adapter.application'), new RecordingInvocationAdapterSession([InvocationAdapterEmission::result(ModelInvocationApplicationFactory::result($source)), null])));
        $second = new ModelInvocationApplication(ModelInvocationApplicationFactory::profile($source), new RecordingInvocationAdapter(new AdapterIdentity('adapter.application'), new RecordingInvocationAdapterSession([InvocationAdapterEmission::result(ModelInvocationApplicationFactory::result($source)), null])));
        $first->run(ModelInvocationApplicationFactory::runInput());

        self::assertSame(ModelInvocationApplicationState::Retired, $first->state());
        self::assertSame(ModelInvocationApplicationState::Ready, $second->state());
    }
}
