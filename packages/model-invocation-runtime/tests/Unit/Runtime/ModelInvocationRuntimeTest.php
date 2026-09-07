<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Unit\Runtime;

use Closure;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterEmission;
use Kongono\ModelInvocationRuntime\Exception\InvalidInvocationRuntimeException;
use Kongono\ModelInvocationRuntime\Exception\InvocationCleanupException;
use Kongono\ModelInvocationRuntime\Exception\InvocationOperationAndCleanupException;
use Kongono\ModelInvocationRuntime\Exception\InvocationOperationException;
use Kongono\ModelInvocationRuntime\Exception\InvocationRejectedException;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;
use Kongono\ModelInvocationRuntime\Runtime\ModelInvocationRuntime;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationAdapter;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationAdapterSession;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationCancellation;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationMonotonicClock;
use Kongono\ModelInvocationRuntime\Tests\Fixture\RuntimeFixture;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class ModelInvocationRuntimeTest extends TestCase
{
    public function testNonstreamingSuccessUsesOneDirectSessionAndTrailingNullProof(): void
    {
        $source = RuntimeFixture::source();
        $normalized = RuntimeFixture::result($source, 'ok');
        $session = new FakeInvocationAdapterSession([InvocationAdapterEmission::result($normalized), null]);
        $adapter = new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $session);

        $result = (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, $adapter));

        self::assertSame(InvocationOutcome::Succeeded, $result->receipt()->outcome());
        self::assertSame($normalized, $result->result());
        self::assertSame(1, $adapter->openCalls);
        self::assertSame(2, $session->nextCalls);
        self::assertSame(1, $session->closeCalls);
        self::assertSame(2, $result->retainedEvidenceBytes());
    }

    public function testExactStreamingSequenceUsageCostAndFailedTerminalAreReturned(): void
    {
        $source = RuntimeFixture::source(streaming: true);
        $resultEvidence = RuntimeFixture::result($source, 'done', RuntimeFixture::usage(5), RuntimeFixture::cost('1.250'));
        $session = new FakeInvocationAdapterSession([
            RuntimeFixture::event($source, 1, StreamEventKind::Started),
            RuntimeFixture::event($source, 2, StreamEventKind::ContentDelta, 'a'),
            RuntimeFixture::event($source, 3, StreamEventKind::Usage, usage: RuntimeFixture::usage(4)),
            RuntimeFixture::event($source, 4, StreamEventKind::Completed),
            InvocationAdapterEmission::result($resultEvidence),
            null,
        ]);
        $runtimeResult = (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $session)));
        self::assertSame(InvocationOutcome::Succeeded, $runtimeResult->receipt()->outcome());
        self::assertCount(4, $runtimeResult->events());
        self::assertSame(5, $runtimeResult->retainedEvidenceBytes());

        $failedSession = new FakeInvocationAdapterSession([
            RuntimeFixture::event($source, 1, StreamEventKind::Started),
            RuntimeFixture::event($source, 2, StreamEventKind::Failed),
            null,
        ]);
        $failed = (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $failedSession)));
        self::assertSame(InvocationOutcome::Failed, $failed->receipt()->outcome());
        self::assertNull($failed->result());
    }

    public function testPreflightRejectionHasZeroAttemptsAndNoOpen(): void
    {
        $source = RuntimeFixture::source();
        $adapter = new FakeInvocationAdapter(new AdapterIdentity('adapter.wrong'), new FakeInvocationAdapterSession([]));
        try {
            (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, $adapter));
            self::fail('Expected rejection.');
        } catch (InvocationRejectedException $failure) {
            self::assertSame(0, $failure->receipt()->attemptCount());
            self::assertSame(InvocationOutcome::Rejected, $failure->receipt()->outcome());
            self::assertSame(0, $adapter->openCalls);
        }
    }

    public function testCancellationBeforeOpenReturnsCancelledWithoutSession(): void
    {
        $source = RuntimeFixture::source();
        $adapter = new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), new FakeInvocationAdapterSession([]));
        $result = (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, $adapter, new FakeInvocationCancellation([true])));
        self::assertSame(InvocationOutcome::Cancelled, $result->receipt()->outcome());
        self::assertSame(0, $adapter->openCalls);
    }

    public function testMalformedAndStaleArtifactsRejectBeforeOpening(): void
    {
        $source = RuntimeFixture::source();
        $staleSource = RuntimeFixture::source(maxOutputBytes: 31);
        $staleBytes = RuntimeFixture::request(
            $staleSource,
            new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), new FakeInvocationAdapterSession([])),
        )->artifactBytes();
        foreach (['{', $staleBytes] as $artifactBytes) {
            $adapter = new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), new FakeInvocationAdapterSession([]));
            try {
                (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, $adapter, artifactBytes: $artifactBytes));
                self::fail('Expected preflight rejection.');
            } catch (InvocationRejectedException $failure) {
                self::assertSame(0, $failure->receipt()->attemptCount());
                self::assertSame(0, $adapter->openCalls);
            }
        }
    }

    public function testCancellationBeforeAndAfterPullClosesExactlyOnce(): void
    {
        $source = RuntimeFixture::source();
        foreach ([[false, true], [false, false, true]] as $cancellationValues) {
            $session = new FakeInvocationAdapterSession([InvocationAdapterEmission::result(RuntimeFixture::result($source)), null]);
            $result = (new ModelInvocationRuntime())->run(RuntimeFixture::request(
                $source,
                new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $session),
                new FakeInvocationCancellation($cancellationValues),
            ));
            self::assertSame(InvocationOutcome::Cancelled, $result->receipt()->outcome());
            self::assertSame(1, $result->receipt()->attemptCount());
            self::assertSame(1, $session->closeCalls);
        }
    }

    public function testCancellationAfterFailedTerminalProofReturnsCancelled(): void
    {
        $source = RuntimeFixture::source(streaming: true);
        $session = new FakeInvocationAdapterSession([
            RuntimeFixture::event($source, 1, StreamEventKind::Started),
            RuntimeFixture::event($source, 2, StreamEventKind::Failed),
            null,
        ]);
        $cancellation = new FakeInvocationCancellation([false, false, false, false, false, false, true]);
        $result = (new ModelInvocationRuntime())->run(RuntimeFixture::request(
            $source,
            new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $session),
            $cancellation,
        ));
        self::assertSame(InvocationOutcome::Cancelled, $result->receipt()->outcome());
        self::assertSame(1, $session->closeCalls);
    }

    public function testClockRegressionAndOutputPlusOneAreOperationFailures(): void
    {
        $source = RuntimeFixture::source();
        $adapter = new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), new FakeInvocationAdapterSession([]));
        try {
            (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, $adapter, clock: new FakeInvocationMonotonicClock([10, 9])));
            self::fail('Expected clock failure.');
        } catch (InvocationOperationException $failure) {
            self::assertSame('KGN-MODEL-RUNTIME-1422', $failure->diagnosticId());
        }

        $bounded = RuntimeFixture::source(maxOutputBytes: 2);
        $session = new FakeInvocationAdapterSession([InvocationAdapterEmission::result(RuntimeFixture::result($bounded, 'abc'))]);
        $this->expectException(InvocationOperationException::class);
        (new ModelInvocationRuntime())->run(RuntimeFixture::request($bounded, new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $session)));
    }

    public function testDurationOutputStreamAndRetainedEvidenceExactBoundariesAndPlusOne(): void
    {
        $durationSource = RuntimeFixture::source(maxDurationMilliseconds: 3);
        $exactSession = new FakeInvocationAdapterSession([InvocationAdapterEmission::result(RuntimeFixture::result($durationSource)), null]);
        $exact = (new ModelInvocationRuntime())->run(RuntimeFixture::request(
            $durationSource,
            new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $exactSession),
            clock: new FakeInvocationMonotonicClock([5, 5, 5, 8, 8, 8, 8]),
        ));
        self::assertSame(3, $exact->elapsedMilliseconds());

        $plusSession = new FakeInvocationAdapterSession([InvocationAdapterEmission::result(RuntimeFixture::result($durationSource))]);
        $durationFailure = $this->operationFailure(
            $durationSource,
            $plusSession,
            clock: new FakeInvocationMonotonicClock([5, 5, 5, 9, 9]),
        );
        self::assertSame('KGN-MODEL-RUNTIME-1421', $durationFailure->diagnosticId());
        self::assertSame(1, $plusSession->closeCalls);

        $outputSource = RuntimeFixture::source(maxOutputBytes: 2, maxRetainedEvidenceBytes: 2);
        $outputExact = new FakeInvocationAdapterSession([InvocationAdapterEmission::result(RuntimeFixture::result($outputSource, 'ok')), null]);
        self::assertSame(InvocationOutcome::Succeeded, $this->executeRuntime($outputSource, $outputExact)->receipt()->outcome());
        $outputPlus = new FakeInvocationAdapterSession([InvocationAdapterEmission::result(RuntimeFixture::result($outputSource, 'abc'))]);
        self::assertSame('KGN-MODEL-RUNTIME-1421', $this->operationFailure($outputSource, $outputPlus)->diagnosticId());

        $streamSource = RuntimeFixture::source(streaming: true, maxOutputBytes: 2, maxStreamEvents: 2, maxRetainedEvidenceBytes: 2);
        $streamExact = new FakeInvocationAdapterSession([
            RuntimeFixture::event($streamSource, 1, StreamEventKind::Started),
            RuntimeFixture::event($streamSource, 2, StreamEventKind::Failed),
            null,
        ]);
        self::assertSame(InvocationOutcome::Failed, $this->executeRuntime($streamSource, $streamExact)->receipt()->outcome());
        $streamPlus = new FakeInvocationAdapterSession([
            RuntimeFixture::event($streamSource, 1, StreamEventKind::Started),
            RuntimeFixture::event($streamSource, 2, StreamEventKind::ContentDelta, 'a'),
            RuntimeFixture::event($streamSource, 3, StreamEventKind::Failed),
        ]);
        self::assertSame('KGN-MODEL-RUNTIME-1421', $this->operationFailure($streamSource, $streamPlus)->diagnosticId());

        $retainedSource = RuntimeFixture::source(streaming: true, maxOutputBytes: 2, maxRetainedEvidenceBytes: 2);
        $retainedExact = new FakeInvocationAdapterSession([
            RuntimeFixture::event($retainedSource, 1, StreamEventKind::Started),
            RuntimeFixture::event($retainedSource, 2, StreamEventKind::ContentDelta, 'a'),
            RuntimeFixture::event($retainedSource, 3, StreamEventKind::Completed),
            InvocationAdapterEmission::result(RuntimeFixture::result($retainedSource, 'b')),
            null,
        ]);
        self::assertSame(2, $this->executeRuntime($retainedSource, $retainedExact)->retainedEvidenceBytes());
        $retainedPlus = new FakeInvocationAdapterSession([
            RuntimeFixture::event($retainedSource, 1, StreamEventKind::Started),
            RuntimeFixture::event($retainedSource, 2, StreamEventKind::ContentDelta, 'a'),
            RuntimeFixture::event($retainedSource, 3, StreamEventKind::Completed),
            InvocationAdapterEmission::result(RuntimeFixture::result($retainedSource, 'ok')),
        ]);
        self::assertSame('KGN-MODEL-RUNTIME-1421', $this->operationFailure($retainedSource, $retainedPlus)->diagnosticId());
    }

    public function testInvalidStreamingOrdersModesIdentitiesAndTrailingEvidenceReject(): void
    {
        $source = RuntimeFixture::source(streaming: true);
        $wrongIdentity = new InvocationIdentity('invocation.wrong');
        $sequences = [
            [RuntimeFixture::event($source, 1, StreamEventKind::ContentDelta, 'a')],
            [RuntimeFixture::event($source, 2, StreamEventKind::Started)],
            [RuntimeFixture::event($source, 1, StreamEventKind::Started), RuntimeFixture::event($source, 3, StreamEventKind::ContentDelta, 'a')],
            [RuntimeFixture::event($source, 1, StreamEventKind::Started), null],
            [RuntimeFixture::event($source, 1, StreamEventKind::Started), InvocationAdapterEmission::result(RuntimeFixture::result($source))],
            [RuntimeFixture::event($source, 1, StreamEventKind::Started), RuntimeFixture::event($source, 2, StreamEventKind::Failed), RuntimeFixture::event($source, 3, StreamEventKind::Failed)],
            [RuntimeFixture::event($source, 1, StreamEventKind::Started), RuntimeFixture::event($source, 2, StreamEventKind::ActionName, 'action.test')],
            [RuntimeFixture::event($source, 1, StreamEventKind::Started, invocationIdentity: $wrongIdentity)],
        ];
        foreach ($sequences as $emissions) {
            $session = new FakeInvocationAdapterSession($emissions);
            $failure = $this->operationFailure($source, $session);
            self::assertSame('KGN-MODEL-RUNTIME-1420', $failure->diagnosticId());
            self::assertSame(1, $session->closeCalls);
        }
    }

    public function testResultIdentityProfileModeAndCancelledFinishReject(): void
    {
        $source = RuntimeFixture::source();
        $wrongProfile = new ModelProfileReference(
            ContractVersion::Version1,
            new ProfileIdentity('profile.wrong'),
            new ProviderIdentity('provider.test'),
            new ModelIdentity('model.test'),
            new ProviderCapabilitySet(ProviderCapability::cases()),
        );
        $results = [
            RuntimeFixture::result($source, invocationIdentity: new InvocationIdentity('invocation.wrong')),
            RuntimeFixture::result($source, profile: $wrongProfile),
            RuntimeFixture::result($source, mode: ResultMode::Structured),
            RuntimeFixture::result($source, finishReason: FinishReason::Cancelled),
        ];
        foreach ($results as $result) {
            $session = new FakeInvocationAdapterSession([InvocationAdapterEmission::result($result)]);
            self::assertSame('KGN-MODEL-RUNTIME-1420', $this->operationFailure($source, $session)->diagnosticId());
        }
    }

    public function testUsageAndCostEvidenceBoundariesAreExactWithoutFloatComparison(): void
    {
        $source = RuntimeFixture::source(maxUsageQuantity: 10, maxCostAmount: '1.25');
        $accepted = new FakeInvocationAdapterSession([
            InvocationAdapterEmission::result(RuntimeFixture::result($source, usage: RuntimeFixture::usage(10), cost: RuntimeFixture::cost('1.2500'))),
            null,
        ]);
        self::assertSame(InvocationOutcome::Succeeded, $this->executeRuntime($source, $accepted)->receipt()->outcome());

        foreach ([
            [RuntimeFixture::source(maxUsageQuantity: 10), RuntimeFixture::usage(11), null],
            [RuntimeFixture::source(includeUsageLimit: false), RuntimeFixture::usage(1), null],
            [RuntimeFixture::source(maxCostAmount: '1.25'), null, RuntimeFixture::cost('1.251')],
            [RuntimeFixture::source(maxCostAmount: '1.25'), null, RuntimeFixture::cost('1.3')],
            [RuntimeFixture::source(includeCostLimit: false), null, RuntimeFixture::cost('1')],
            [RuntimeFixture::source(), null, RuntimeFixture::cost('1', 'EUR')],
        ] as [$boundedSource, $usage, $cost]) {
            $session = new FakeInvocationAdapterSession([
                InvocationAdapterEmission::result(RuntimeFixture::result($boundedSource, usage: $usage, cost: $cost)),
            ]);
            self::assertSame('KGN-MODEL-RUNTIME-1421', $this->operationFailure($boundedSource, $session)->diagnosticId());
        }

        $streaming = RuntimeFixture::source(streaming: true);
        foreach ([
            RuntimeFixture::result($streaming, usage: RuntimeFixture::usage(4)),
            RuntimeFixture::result($streaming),
        ] as $finalResult) {
            $session = new FakeInvocationAdapterSession([
                RuntimeFixture::event($streaming, 1, StreamEventKind::Started),
                RuntimeFixture::event($streaming, 2, StreamEventKind::Usage, usage: RuntimeFixture::usage(5)),
                RuntimeFixture::event($streaming, 3, StreamEventKind::Completed),
                InvocationAdapterEmission::result($finalResult),
            ]);
            self::assertSame('KGN-MODEL-RUNTIME-1420', $this->operationFailure($streaming, $session)->diagnosticId());
        }
    }

    public function testAdapterClockAndCancellationFailuresPreserveExactPrimaryAndOpenFailureDoesNotClose(): void
    {
        $source = RuntimeFixture::source();
        $identityFailure = new RuntimeException('identity secret');
        $identityAdapter = new FakeInvocationAdapter(
            new AdapterIdentity('adapter.test'),
            new FakeInvocationAdapterSession([]),
            identityFailure: $identityFailure,
        );
        try {
            (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, $identityAdapter));
            self::fail('Expected identity rejection.');
        } catch (InvocationRejectedException $failure) {
            self::assertSame($identityFailure, $failure->getPrevious());
        }

        $openFailure = new RuntimeException('open secret');
        $unopenedSession = new FakeInvocationAdapterSession([]);
        $openAdapter = new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $unopenedSession, $openFailure);
        try {
            (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, $openAdapter));
            self::fail('Expected open failure.');
        } catch (InvocationOperationException $failure) {
            self::assertSame($openFailure, $failure->getPrevious());
            self::assertSame(0, $unopenedSession->closeCalls);
        }

        foreach ([
            [new FakeInvocationCancellation([new RuntimeException('cancellation secret')]), new FakeInvocationMonotonicClock()],
            [new FakeInvocationCancellation(), new FakeInvocationMonotonicClock([new RuntimeException('clock secret')])],
        ] as [$cancellation, $clock]) {
            $adapter = new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), new FakeInvocationAdapterSession([]));
            try {
                (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, $adapter, $cancellation, $clock));
                self::fail('Expected probe failure.');
            } catch (InvocationOperationException $failure) {
                self::assertSame('KGN-MODEL-RUNTIME-1422', $failure->diagnosticId());
                self::assertStringContainsString('secret', (string) $failure->getPrevious()?->getMessage());
            }
        }
    }

    public function testDiagnosticsAndStringificationDoNotDiscloseCallerOrThrowableValues(): void
    {
        $source = RuntimeFixture::source();
        $primary = new RuntimeException('credential-secret endpoint-secret');
        $session = new FakeInvocationAdapterSession([$primary]);
        $failure = $this->operationFailure($source, $session);
        self::assertSame($primary, $failure->getPrevious());
        $presented = implode('|', [
            $failure->getMessage(),
            (string) $failure,
            $failure->diagnosticTitle(),
            $failure->diagnosticRemediation(),
            $failure->diagnosticContext()->operation(),
            $failure->diagnosticContext()->violatedInvariant(),
            ...array_map(static fn($field): string => (string) $field->value(), $failure->diagnosticContext()->fields()),
        ]);
        foreach (['secret input', 'provider.test', 'model.test', 'adapter.test', 'plan.test', 'receipt.test', 'credential-secret', 'endpoint-secret'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $presented);
        }
    }

    public function testCleanupMatricesPreserveExactFailuresAndCloseOnce(): void
    {
        $source = RuntimeFixture::source();
        $closeFailure = new RuntimeException('private close');
        $session = new FakeInvocationAdapterSession([InvocationAdapterEmission::result(RuntimeFixture::result($source)), null], $closeFailure);
        try {
            (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $session)));
            self::fail('Expected cleanup failure.');
        } catch (InvocationCleanupException $failure) {
            self::assertSame([$closeFailure], $failure->cleanupFailures());
            self::assertSame(1, $session->closeCalls);
        }

        $pullFailure = new RuntimeException('private pull');
        $closeFailure = new RuntimeException('private close two');
        $session = new FakeInvocationAdapterSession([$pullFailure], $closeFailure);
        try {
            (new ModelInvocationRuntime())->run(RuntimeFixture::request($source, new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $session)));
            self::fail('Expected combined failure.');
        } catch (InvocationOperationAndCleanupException $failure) {
            self::assertInstanceOf(InvocationOperationException::class, $failure->primaryFailure());
            self::assertSame($pullFailure, $failure->primaryFailure()->getPrevious());
            self::assertSame([$closeFailure], $failure->cleanupFailures());
        }
    }

    public function testSequentialReuseAndReentrancyRejectionLeaveNoOperationState(): void
    {
        $source = RuntimeFixture::source();
        $runtime = new ModelInvocationRuntime();
        $nested = null;
        $request = null;
        $callback = Closure::fromCallable(function () use ($runtime, &$request, &$nested): void {
            try {
                self::assertInstanceOf(InvocationRuntimeRequest::class, $request);
                $runtime->run($request);
            } catch (Throwable $failure) {
                $nested = $failure;
            }
        });
        $session = new FakeInvocationAdapterSession([InvocationAdapterEmission::result(RuntimeFixture::result($source)), null], onNext: $callback);
        $request = RuntimeFixture::request($source, new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $session));
        self::assertSame(InvocationOutcome::Succeeded, $runtime->run($request)->receipt()->outcome());
        self::assertInstanceOf(InvalidInvocationRuntimeException::class, $nested);

        $secondSession = new FakeInvocationAdapterSession([InvocationAdapterEmission::result(RuntimeFixture::result($source)), null]);
        self::assertSame(InvocationOutcome::Succeeded, $runtime->run(RuntimeFixture::request($source, new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $secondSession)))->receipt()->outcome());
    }

    private function executeRuntime(\Kongono\ModelInvocationBuild\Plan\InvocationPlanSource $source, FakeInvocationAdapterSession $session): \Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult
    {
        return (new ModelInvocationRuntime())->run(RuntimeFixture::request(
            $source,
            new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $session),
        ));
    }

    private function operationFailure(
        \Kongono\ModelInvocationBuild\Plan\InvocationPlanSource $source,
        FakeInvocationAdapterSession $session,
        ?FakeInvocationMonotonicClock $clock = null,
    ): InvocationOperationException {
        try {
            (new ModelInvocationRuntime())->run(RuntimeFixture::request(
                $source,
                new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), $session),
                clock: $clock,
            ));
            self::fail('Expected operation failure.');
        } catch (InvocationOperationException $failure) {
            return $failure;
        }
    }
}
