<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Unit\Runtime;

use Error;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseRequest;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseState;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseCleanupException;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseOperationAndCleanupException;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseOperationException;
use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeRequest;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailureKind;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Kongono\ExternalServiceCredentialRuntime\Runtime\CredentialLeaseRuntime;
use Kongono\ExternalServiceCredentialRuntime\Tests\Fixture\FakeCredentialLease;
use Kongono\ExternalServiceCredentialRuntime\Tests\Fixture\FakeCredentialLeaseSource;
use Kongono\ExternalServiceCredentialRuntime\Tests\Fixture\FakeCredentialMonotonicClock;
use Kongono\ExternalServiceCredentialRuntime\Tests\Fixture\RecordingCredentialMaterialConsumer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class CredentialLeaseRuntimeTest extends TestCase
{
    public function testSuccessUsesOneExactSourceMaterialBoundaryAndRetirementWithFourObservations(): void
    {
        $events = [];
        $observer = static function (string $event) use (&$events): void {
            $events[] = $event;
        };
        [$request, $lease, $source, $consumer, $clock] = $this->fixture([100, 101, 102, 103], observer: $observer);
        $result = (new CredentialLeaseRuntime())->run($request);

        self::assertSame(CredentialLeaseRuntimeOutcome::Succeeded, $result->outcome());
        self::assertSame(CredentialLeaseRuntimeState::Retired, $result->state());
        self::assertNull($result->failure());
        self::assertSame(3, $result->elapsedMilliseconds());
        self::assertSame(1, $result->acquisitionAttemptCount());
        self::assertSame(1, $result->materialAccessAttemptCount());
        self::assertSame(1, $result->retirementAttemptCount());
        self::assertTrue($result->cleanupComplete());
        self::assertSame(1, $source->calls);
        self::assertSame($request->leaseRequest(), $source->receivedRequest);
        self::assertSame(1, $lease->materialCalls);
        self::assertSame(1, $lease->retireCalls);
        self::assertSame(['borrowed-random-value'], $consumer->received);
        self::assertSame(4, $clock->calls);
        self::assertSame([
            'clock',
            'source.acquire',
            'lease.identity',
            'lease.reference',
            'lease.purpose',
            'lease.state',
            'clock',
            'lease.with_material',
            'consumer.consume',
            'lease.state',
            'clock',
            'lease.retire',
            'lease.state',
            'clock',
        ], $events);
    }

    public function testInclusiveLifetimeIsAccepted(): void
    {
        [$request] = $this->fixture([0, 10, 10, 10], maximumLifetime: 10);
        self::assertSame(10, (new CredentialLeaseRuntime())->run($request)->elapsedMilliseconds());
    }

    /** @return iterable<string, array{list<int>, int, CredentialLeaseRuntimeOutcome}> */
    public static function lifetimeFailures(): iterable
    {
        yield 'before material' => [[0, 11], 0, CredentialLeaseRuntimeOutcome::FailedBeforeMaterial];
        yield 'after material' => [[0, 5, 11], 1, CredentialLeaseRuntimeOutcome::MaterialUseUncertain];
        yield 'after retirement' => [[0, 5, 9, 11], 1, CredentialLeaseRuntimeOutcome::MaterialUseUncertain];
    }

    /** @param list<int> $times */
    #[DataProvider('lifetimeFailures')]
    public function testPlusOneLifetimeFailsAtEveryBoundaryAndStillRetires(array $times, int $materialAttempts, CredentialLeaseRuntimeOutcome $outcome): void
    {
        [$request, $lease] = $this->fixture($times, maximumLifetime: 10);
        try {
            (new CredentialLeaseRuntime())->run($request);
            self::fail('Plus-one lifetime must fail.');
        } catch (CredentialLeaseOperationException $failure) {
            $evidence = $failure->terminalResult()->failure();
            self::assertNotNull($evidence);
            self::assertSame(CredentialLeaseRuntimeFailureKind::Lifetime, $evidence->kind());
            self::assertSame('lifetime_exceeded', $evidence->reason());
            self::assertSame($outcome, $failure->terminalResult()->outcome());
            self::assertSame($materialAttempts, $failure->terminalResult()->materialAccessAttemptCount());
            self::assertSame(1, $lease->retireCalls);
        }
    }

    /** @return iterable<string, array{list<int>, string}> */
    public static function invalidClocks(): iterable
    {
        yield 'negative start' => [[-1], 'clock_negative'];
        yield 'negative later' => [[0, -1], 'clock_negative'];
        yield 'regression' => [[5, 4], 'clock_regression'];
    }

    /** @param list<int> $times */
    #[DataProvider('invalidClocks')]
    public function testNegativeAndRegressingClocksFailAndAcquiredLeaseRetiresWithoutAnotherClock(array $times, string $reason): void
    {
        [$request, $lease, , , $clock] = $this->fixture($times);
        try {
            (new CredentialLeaseRuntime())->run($request);
            self::fail('Invalid clock must fail.');
        } catch (CredentialLeaseOperationException $failure) {
            self::assertSame($reason, $failure->terminalResult()->failure()?->reason());
            if ('clock_negative' === $reason && 1 === count($times) && -1 === $times[0]) {
                self::assertSame(0, $lease->retireCalls);
            } else {
                self::assertSame(1, $lease->retireCalls);
                self::assertSame(count($times), $clock->calls);
            }
        }
    }

    public function testClockThrowableBeforeAcquisitionPreservesIdentityAndDoesNotAcquire(): void
    {
        $foreign = new Error('clock-foreign');
        [$request, $lease, $source] = $this->fixture([$foreign]);
        try {
            (new CredentialLeaseRuntime())->run($request);
            self::fail('Clock failure must fail.');
        } catch (CredentialLeaseOperationException $failure) {
            self::assertSame($foreign, $failure->getPrevious());
            self::assertSame(0, $source->calls);
            self::assertSame(0, $lease->retireCalls);
            self::assertSame(CredentialLeaseRuntimeOutcome::FailedBeforeAcquisition, $failure->terminalResult()->outcome());
        }
    }

    public function testSourceFailurePreservesIdentityWithoutRetirementAndRuntimeRecovers(): void
    {
        $runtime = new CredentialLeaseRuntime();
        $foreign = new RuntimeException('source-foreign');
        [$failedRequest, $unusedLease, $source] = $this->fixture([0], sourceFailure: $foreign);
        try {
            $runtime->run($failedRequest);
            self::fail('Source failure must fail.');
        } catch (CredentialLeaseOperationException $failure) {
            self::assertSame($foreign, $failure->primaryFailure());
            self::assertSame(1, $source->calls);
            self::assertSame(0, $unusedLease->retireCalls);
        }
        [$successRequest] = $this->fixture([0, 0, 0, 0]);
        self::assertSame(CredentialLeaseRuntimeOutcome::Succeeded, $runtime->run($successRequest)->outcome());
    }

    /** @return iterable<string, array{string, string, CredentialLeaseState, string}> */
    public static function invalidLeaseRelations(): iterable
    {
        yield 'reference' => ['other', 'purpose-a', CredentialLeaseState::Acquired, 'lease_reference_mismatch'];
        yield 'purpose' => ['opaque-a', 'purpose-b', CredentialLeaseState::Acquired, 'lease_purpose_mismatch'];
        yield 'state' => ['opaque-a', 'purpose-a', CredentialLeaseState::InUse, 'lease_state_invalid'];
    }

    #[DataProvider('invalidLeaseRelations')]
    public function testInitialLeaseRelationsFailBeforeMaterialAndRetire(string $reference, string $purpose, CredentialLeaseState $state, string $reason): void
    {
        [$request, $lease] = $this->fixture([0], referenceValue: $reference, purposeValue: $purpose, initialState: $state);
        try {
            (new CredentialLeaseRuntime())->run($request);
            self::fail('Invalid lease relation must fail.');
        } catch (CredentialLeaseOperationException $failure) {
            self::assertSame($reason, $failure->terminalResult()->failure()?->reason());
            self::assertSame(0, $lease->materialCalls);
            self::assertSame(1, $lease->retireCalls);
            self::assertSame(CredentialLeaseRuntimeOutcome::FailedBeforeMaterial, $failure->terminalResult()->outcome());
        }
    }

    public function testPostUseStateViolationMakesMaterialUseUncertainAndRetires(): void
    {
        [$request, $lease] = $this->fixture([0, 0], postMaterialState: CredentialLeaseState::InUse);
        try {
            (new CredentialLeaseRuntime())->run($request);
            self::fail('Post-use state violation must fail.');
        } catch (CredentialLeaseOperationException $failure) {
            self::assertSame(CredentialLeaseRuntimeOutcome::MaterialUseUncertain, $failure->terminalResult()->outcome());
            self::assertSame('lease_state_invalid', $failure->terminalResult()->failure()?->reason());
            self::assertSame(1, $lease->retireCalls);
        }
    }

    /** @return iterable<string, array{string, Throwable}> */
    public static function failingLeaseAccessors(): iterable
    {
        yield 'identity error' => ['identity', new Error('identity-foreign')];
        yield 'reference exception' => ['reference', new RuntimeException('reference-foreign')];
        yield 'purpose error' => ['purpose', new Error('purpose-foreign')];
        yield 'state exception' => ['state', new RuntimeException('state-foreign')];
    }

    #[DataProvider('failingLeaseAccessors')]
    public function testLeaseAccessorThrowablesArePreservedAndRetirementStillRuns(string $accessor, Throwable $foreign): void
    {
        [$request, $lease] = $this->fixture([0], accessorFailures: [$accessor => [$foreign]]);
        try {
            (new CredentialLeaseRuntime())->run($request);
            self::fail('Lease accessor failure must fail.');
        } catch (CredentialLeaseOperationException $failure) {
            self::assertSame($foreign, $failure->primaryFailure());
            self::assertSame(CredentialLeaseRuntimeOutcome::FailedBeforeMaterial, $failure->terminalResult()->outcome());
            self::assertSame(1, $lease->retireCalls);
        }
    }

    public function testConsumerFailureIsPrimaryAndRuntimeRecovers(): void
    {
        $runtime = new CredentialLeaseRuntime();
        $foreign = new RuntimeException('consumer-foreign');
        [$request, $lease] = $this->fixture([0, 0], materialFailure: $foreign);
        try {
            $runtime->run($request);
            self::fail('Consumer boundary failure must fail.');
        } catch (CredentialLeaseOperationException $failure) {
            self::assertSame($foreign, $failure->primaryFailure());
            self::assertSame(CredentialLeaseRuntimeOutcome::MaterialUseUncertain, $failure->terminalResult()->outcome());
            self::assertSame(1, $lease->retireCalls);
        }
        [$successRequest] = $this->fixture([0, 0, 0, 0]);
        self::assertSame(CredentialLeaseRuntimeOutcome::Succeeded, $runtime->run($successRequest)->outcome());
    }

    public function testRetirementThrowAndNonRetiredStateAreUncertain(): void
    {
        $runtime = new CredentialLeaseRuntime();
        $foreign = new Error('retirement-foreign');
        [$throwRequest, $throwLease] = $this->fixture([0, 0, 0], retirementFailure: $foreign);
        try {
            $runtime->run($throwRequest);
            self::fail('Retirement failure must fail.');
        } catch (CredentialLeaseCleanupException $failure) {
            self::assertSame([$foreign], $failure->cleanupFailures());
            self::assertSame('retirement_failed', $failure->terminalResult()->failure()?->reason());
            self::assertSame(1, $throwLease->retireCalls);
        }
        [$recoveryRequest] = $this->fixture([0, 0, 0, 0]);
        self::assertSame(CredentialLeaseRuntimeOutcome::Succeeded, $runtime->run($recoveryRequest)->outcome());

        [$stateRequest] = $this->fixture([0, 0, 0], postRetirementState: CredentialLeaseState::Uncertain);
        try {
            (new CredentialLeaseRuntime())->run($stateRequest);
            self::fail('Non-retired final state must fail.');
        } catch (CredentialLeaseCleanupException $failure) {
            self::assertSame('retirement_state_invalid', $failure->terminalResult()->failure()?->reason());
        }
    }

    public function testCombinedFailurePreservesPrimaryAndOneCleanupFailure(): void
    {
        $primary = new RuntimeException('consumer-primary');
        $cleanup = new Error('retirement-cleanup');
        [$request, $lease] = $this->fixture([0, 0], materialFailure: $primary, retirementFailure: $cleanup);
        try {
            (new CredentialLeaseRuntime())->run($request);
            self::fail('Combined failure must fail.');
        } catch (CredentialLeaseOperationAndCleanupException $failure) {
            self::assertSame($primary, $failure->primaryFailure());
            self::assertSame($primary, $failure->getPrevious());
            self::assertSame([$cleanup], $failure->cleanupFailures());
            self::assertSame(1, $lease->retireCalls);
            self::assertSame(CredentialLeaseRuntimeOutcome::RetirementUncertain, $failure->terminalResult()->outcome());
        }
    }

    public function testSameInstanceReentrancyFailsBeforeNestedCollaboratorAndOuterRetiresOnce(): void
    {
        $runtime = new CredentialLeaseRuntime();
        [$nestedRequest, , $nestedSource, , $nestedClock] = $this->fixture([0, 0, 0, 0]);
        $nestedFailure = null;
        [$outerRequest, $outerLease] = $this->fixture(
            [0, 0, 0, 0],
            duringMaterial: static function () use ($runtime, $nestedRequest, &$nestedFailure): void {
                try {
                    $runtime->run($nestedRequest);
                } catch (InvalidCredentialLeaseRuntimeException $failure) {
                    $nestedFailure = $failure;
                }
            },
        );
        self::assertSame(CredentialLeaseRuntimeOutcome::Succeeded, $runtime->run($outerRequest)->outcome());
        self::assertInstanceOf(InvalidCredentialLeaseRuntimeException::class, $nestedFailure);
        self::assertSame('reentrancy', $nestedFailure->violation());
        self::assertSame(0, $nestedSource->calls);
        self::assertSame(0, $nestedClock->calls);
        self::assertSame(1, $outerLease->retireCalls);
    }

    public function testSeparateInstancesMayOverlapAndSequentialSuccessReuseIsIsolated(): void
    {
        $outer = new CredentialLeaseRuntime();
        $inner = new CredentialLeaseRuntime();
        [$innerRequest] = $this->fixture([0, 0, 0, 0]);
        $innerOutcome = null;
        [$outerRequest] = $this->fixture(
            [0, 0, 0, 0],
            duringMaterial: static function () use ($inner, $innerRequest, &$innerOutcome): void {
                $innerOutcome = $inner->run($innerRequest)->outcome();
            },
        );
        self::assertSame(CredentialLeaseRuntimeOutcome::Succeeded, $outer->run($outerRequest)->outcome());
        self::assertSame(CredentialLeaseRuntimeOutcome::Succeeded, $innerOutcome);
        [$again] = $this->fixture([1, 1, 1, 1]);
        self::assertSame(CredentialLeaseRuntimeOutcome::Succeeded, $outer->run($again)->outcome());
        self::assertSame(['operationActive'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), (new \ReflectionClass($outer))->getProperties()));
    }

    /**
     * @param list<int|Throwable> $times
     * @param array<string, list<Throwable>> $accessorFailures
     * @param null|\Closure(string): void $observer
     * @return array{CredentialLeaseRuntimeRequest, FakeCredentialLease, FakeCredentialLeaseSource, RecordingCredentialMaterialConsumer, FakeCredentialMonotonicClock}
     */
    private function fixture(
        array $times,
        int $maximumLifetime = 100,
        ?Throwable $sourceFailure = null,
        string $referenceValue = 'opaque-a',
        string $purposeValue = 'purpose-a',
        CredentialLeaseState $initialState = CredentialLeaseState::Acquired,
        ?Throwable $materialFailure = null,
        ?Throwable $retirementFailure = null,
        CredentialLeaseState $postMaterialState = CredentialLeaseState::Acquired,
        CredentialLeaseState $postRetirementState = CredentialLeaseState::Retired,
        ?\Closure $duringMaterial = null,
        array $accessorFailures = [],
        ?\Closure $observer = null,
    ): array {
        $identity = new CredentialSourceIdentity('source-a');
        $expectedReference = new CredentialReference($identity, 'opaque-a');
        $expectedPurpose = new CredentialPurpose('purpose-a');
        $leaseRequest = new CredentialLeaseRequest(new ExternalServiceOperationIdentity('operation-a'), $expectedReference, $expectedPurpose, $maximumLifetime);
        $lease = new FakeCredentialLease(
            new CredentialLeaseIdentity('lease-a'),
            new CredentialReference($identity, $referenceValue),
            new CredentialPurpose($purposeValue),
            $initialState,
            'borrowed-random-value',
            null,
            $retirementFailure,
            $postMaterialState,
            $postRetirementState,
            $duringMaterial,
            $accessorFailures,
            $observer,
        );
        $source = new FakeCredentialLeaseSource($sourceFailure ?? $lease, $observer);
        $consumer = new RecordingCredentialMaterialConsumer($materialFailure, $observer);
        $clock = new FakeCredentialMonotonicClock($times, $observer);
        $request = new CredentialLeaseRuntimeRequest($leaseRequest, $identity, $source, $consumer, $clock);

        return [$request, $lease, $source, $consumer, $clock];
    }
}
