<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Tests\Unit\Runtime;

use Error;
use Kongono\Http\Message\Message\Response;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressApplicationLifecycleException;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressApplicationOperationException;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplication;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplicationState;
use Kongono\NetworkEgressApplication\Tests\Support\NetworkEgressApplicationFactory;
use Kongono\NetworkEgressApplication\Tests\Support\RecordingAuthorityCurrentness;
use Kongono\NetworkEgressApplication\Tests\Support\RecordingCancellation;
use Kongono\NetworkEgressApplication\Tests\Support\RecordingResolver;
use Kongono\NetworkEgressApplication\Tests\Support\RecordingTransport;
use Kongono\NetworkEgressApplication\Tests\Support\RecordingTransportSession;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressRuntime\Exception\EgressCleanupException;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NetworkEgressApplicationTest extends TestCase
{
    public function testOneRunPropagatesExactInputDelegatesOnceAndRetires(): void
    {
        $source = NetworkEgressApplicationFactory::policy();
        $authority = new RecordingAuthorityCurrentness();
        $resolver = new RecordingResolver(NetworkEgressApplicationFactory::resolution());
        $session = NetworkEgressApplicationFactory::session();
        $transport = new RecordingTransport([NetworkEgressApplicationFactory::capabilities()], $session);
        $application = new NetworkEgressApplication(NetworkEgressApplicationFactory::profile($source), $resolver, $transport);
        $input = NetworkEgressApplicationFactory::runInput($source, $authority);

        $result = $application->run($input);

        self::assertSame(EgressOutcome::Succeeded, $result->operationResult()->receipt()->outcome());
        self::assertSame(CancellationState::NotRequested, $result->operationResult()->receipt()->cancellation()->state());
        self::assertSame(1, $resolver->calls);
        self::assertSame($input->operation()->origin(), $resolver->origin);
        self::assertSame(1, $transport->openCalls);
        self::assertSame($input->attemptIdentity(), $transport->request?->attemptIdentity());
        self::assertSame(1, $session->closeCalls);
        self::assertSame($input->operation()->authorityReference(), $authority->reference);
        self::assertSame(NetworkEgressApplicationState::Retired, $application->state());
        self::assertFalse($application->hasActiveOperation());

        $this->expectException(NetworkEgressApplicationLifecycleException::class);
        $application->run($input);
    }

    public function testCapabilityMismatchFailsBeforeResolverOrTransportEffect(): void
    {
        $resolver = new RecordingResolver(NetworkEgressApplicationFactory::resolution());
        $transport = new RecordingTransport([new TransportCapabilitySet([TransportCapability::Http11])], NetworkEgressApplicationFactory::session());
        try {
            new NetworkEgressApplication(NetworkEgressApplicationFactory::profile(), $resolver, $transport);
            self::fail('Capability mismatch must fail application construction.');
        } catch (NetworkEgressApplicationLifecycleException $failure) {
            self::assertSame('KGN-NET-1545', $failure->diagnosticId());
            self::assertSame(0, $resolver->calls);
            self::assertSame(0, $transport->openCalls);
            self::assertSame(1, $transport->capabilityCalls);
        }
    }

    public function testCancellationRetiresWithoutResolutionOrTransfer(): void
    {
        $source = NetworkEgressApplicationFactory::policy();
        $resolver = new RecordingResolver(NetworkEgressApplicationFactory::resolution());
        $transport = new RecordingTransport([NetworkEgressApplicationFactory::capabilities()], NetworkEgressApplicationFactory::session());
        $application = new NetworkEgressApplication(NetworkEgressApplicationFactory::profile($source), $resolver, $transport);
        $result = $application->run(NetworkEgressApplicationFactory::runInput($source, cancellation: new RecordingCancellation([true])));

        self::assertSame(EgressOutcome::CancelledBeforeEffect, $result->operationResult()->receipt()->outcome());
        self::assertSame(0, $resolver->calls);
        self::assertSame(0, $transport->openCalls);
        self::assertSame(NetworkEgressApplicationState::Retired, $application->state());
    }

    public function testExpectedFailureAndUncertainEffectSemanticsPassThrough(): void
    {
        $source = NetworkEgressApplicationFactory::policy();
        $resolver = new RecordingResolver(NetworkEgressApplicationFactory::resolution());
        $transport = new RecordingTransport([NetworkEgressApplicationFactory::capabilities()], NetworkEgressApplicationFactory::session());
        $application = new NetworkEgressApplication(NetworkEgressApplicationFactory::profile($source), $resolver, $transport);
        $denied = $application->run(NetworkEgressApplicationFactory::runInput($source, new RecordingAuthorityCurrentness([false])));

        self::assertSame(EgressOutcome::RejectedBeforeAttempt, $denied->operationResult()->receipt()->outcome());
        self::assertSame(0, $resolver->calls);
        self::assertSame(0, $transport->openCalls);
        self::assertSame(NetworkEgressApplicationState::Retired, $application->state());

        $terminal = new EgressTransportEmission(
            new EgressTransferEvidence(EgressTransferState::ResponseReceived, 1, 1),
            NegotiatedProtocol::Http11,
            0,
            0,
            new Response(),
            null,
        );
        $session = new RecordingTransportSession([$terminal, $terminal]);
        $application = new NetworkEgressApplication(
            NetworkEgressApplicationFactory::profile($source),
            new RecordingResolver(NetworkEgressApplicationFactory::resolution()),
            new RecordingTransport([NetworkEgressApplicationFactory::capabilities()], $session),
        );
        $uncertain = $application->run(NetworkEgressApplicationFactory::runInput($source));

        self::assertSame(EgressOutcome::UncertainEffect, $uncertain->operationResult()->receipt()->outcome());
        self::assertSame(1, $session->closeCalls);
        self::assertSame(NetworkEgressApplicationState::Retired, $application->state());
    }

    public function testReentrancyFailsBeforeSecondResolverOrTransportEffect(): void
    {
        $source = NetworkEgressApplicationFactory::policy();
        $probe = new class {
            public ?NetworkEgressApplication $application = null;
            public ?NetworkEgressApplicationLifecycleException $failure = null;

            public function __invoke(): void
            {
                if (!$this->application instanceof NetworkEgressApplication) {
                    return;
                }
                try {
                    $this->application->run(NetworkEgressApplicationFactory::runInput());
                } catch (NetworkEgressApplicationLifecycleException $failure) {
                    $this->failure = $failure;
                }
            }
        };
        $resolver = new RecordingResolver(NetworkEgressApplicationFactory::resolution(), $probe(...));
        $transport = new RecordingTransport([NetworkEgressApplicationFactory::capabilities()], NetworkEgressApplicationFactory::session());
        $application = new NetworkEgressApplication(NetworkEgressApplicationFactory::profile($source), $resolver, $transport);
        $probe->application = $application;
        $application->run(NetworkEgressApplicationFactory::runInput($source));

        self::assertInstanceOf(NetworkEgressApplicationLifecycleException::class, $probe->failure);
        self::assertSame(1, $resolver->calls);
        self::assertSame(1, $transport->openCalls);
    }

    public function testUnexpectedNonErrorIsWrappedAndExactErrorIsPreserved(): void
    {
        $source = NetworkEgressApplicationFactory::policy();
        $foreign = new RuntimeException('foreign resolver detail');
        $resolver = new RecordingResolver(NetworkEgressApplicationFactory::resolution(), static function () use ($foreign): never {
            throw $foreign;
        });
        $application = new NetworkEgressApplication(NetworkEgressApplicationFactory::profile($source), $resolver, new RecordingTransport([NetworkEgressApplicationFactory::capabilities()], NetworkEgressApplicationFactory::session()));
        try {
            $application->run(NetworkEgressApplicationFactory::runInput($source));
            self::fail('Foreign non-Error failure must be converted.');
        } catch (NetworkEgressApplicationOperationException $failure) {
            self::assertSame('KGN-NET-1547', $failure->diagnosticId());
            self::assertSame($foreign, $failure->getPrevious()?->getPrevious());
            self::assertStringNotContainsString($foreign->getMessage(), (string) $failure);
        }

        $exact = new Error('exact error identity');
        $resolver = new RecordingResolver(NetworkEgressApplicationFactory::resolution(), static function () use ($exact): never {
            throw $exact;
        });
        $application = new NetworkEgressApplication(NetworkEgressApplicationFactory::profile($source), $resolver, new RecordingTransport([NetworkEgressApplicationFactory::capabilities()], NetworkEgressApplicationFactory::session()));
        try {
            $application->run(NetworkEgressApplicationFactory::runInput($source));
            self::fail('Error must be preserved exactly.');
        } catch (Error $failure) {
            self::assertSame($exact, $failure);
            self::assertSame(NetworkEgressApplicationState::Retired, $application->state());
        }
    }

    public function testCleanupFailureEvidenceRemainsExactThroughPackageWrapper(): void
    {
        $source = NetworkEgressApplicationFactory::policy();
        $cleanup = new RuntimeException('foreign cleanup detail');
        $session = NetworkEgressApplicationFactory::session($cleanup);
        $application = new NetworkEgressApplication(
            NetworkEgressApplicationFactory::profile($source),
            new RecordingResolver(NetworkEgressApplicationFactory::resolution()),
            new RecordingTransport([NetworkEgressApplicationFactory::capabilities()], $session),
        );

        try {
            $application->run(NetworkEgressApplicationFactory::runInput($source));
            self::fail('Cleanup failure must remain visible.');
        } catch (NetworkEgressApplicationOperationException $failure) {
            $lower = $failure->getPrevious();
            self::assertInstanceOf(EgressCleanupException::class, $lower);
            self::assertSame([$cleanup], $lower->cleanupFailures());
            self::assertSame($cleanup, $lower->getPrevious());
        }
        self::assertSame(1, $session->closeCalls);
        self::assertSame(NetworkEgressApplicationState::Retired, $application->state());
    }

    public function testFreshInstancesDoNotShareLifecycleState(): void
    {
        $source = NetworkEgressApplicationFactory::policy();
        $first = new NetworkEgressApplication(NetworkEgressApplicationFactory::profile($source), new RecordingResolver(NetworkEgressApplicationFactory::resolution()), new RecordingTransport([NetworkEgressApplicationFactory::capabilities()], NetworkEgressApplicationFactory::session()));
        $second = new NetworkEgressApplication(NetworkEgressApplicationFactory::profile($source), new RecordingResolver(NetworkEgressApplicationFactory::resolution()), new RecordingTransport([NetworkEgressApplicationFactory::capabilities()], NetworkEgressApplicationFactory::session()));
        $first->run(NetworkEgressApplicationFactory::runInput($source));

        self::assertSame(NetworkEgressApplicationState::Retired, $first->state());
        self::assertSame(NetworkEgressApplicationState::Ready, $second->state());
    }
}
