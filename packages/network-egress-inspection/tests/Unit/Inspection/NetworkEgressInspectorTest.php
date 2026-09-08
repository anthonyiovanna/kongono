<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Tests\Unit\Inspection;

use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressInspection\Exception\InvalidNetworkEgressInspectionException;
use Kongono\NetworkEgressInspection\Exception\NetworkEgressInspectionBindingException;
use Kongono\NetworkEgressInspection\Exception\NetworkEgressRuntimeEvidenceException;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspection;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspectionRequest;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspector;
use Kongono\NetworkEgressInspection\Tests\Support\NetworkEgressInspectionFactory as Fixture;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NetworkEgressInspectorTest extends TestCase
{
    public function testExactProfileAndWiringProduceDeterministicBoundedInspection(): void
    {
        $profile = Fixture::profile();
        $request = new NetworkEgressInspectionRequest($profile, Fixture::wiring($profile));
        $first = (new NetworkEgressInspector())->inspect($request);
        $second = (new NetworkEgressInspector())->inspect($request);

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->identity(), $second->identity());
        self::assertSame(NetworkEgressInspection::SCHEMA, $first->toArray()['schema']);
        self::assertSame(1, $first->allowedOriginCount());
        self::assertSame(2, $first->requiredCapabilityCount());
        self::assertFalse($first->runtimeEvidenceSupplied());
        self::assertSame([], $first->toArray()['runtime_summary']);
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertFalse(str_contains(substr($first->canonicalBytes(), 0, -1), "\n"));
    }

    public function testOptionalEvidenceMustBePaired(): void
    {
        $profile = Fixture::profile();
        $operation = Fixture::operation($profile->source());

        foreach ([
            [$operation, null],
            [null, Fixture::runtime($operation)],
        ] as [$candidateOperation, $runtime]) {
            try {
                new NetworkEgressInspectionRequest($profile, Fixture::wiring($profile), $candidateOperation, $runtime);
                self::fail('Expected paired optional evidence rejection.');
            } catch (InvalidNetworkEgressInspectionException $exception) {
                self::assertSame('KGN-NET-1549', $exception->diagnosticId());
            }
        }
    }

    public function testMixedProfileAndWiringFailClosed(): void
    {
        $profile = Fixture::profile(Fixture::source('first'));
        $other = Fixture::profile(Fixture::source('second'));

        $this->expectException(NetworkEgressInspectionBindingException::class);
        (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest($profile, Fixture::wiring($other)));
    }

    public function testMismatchedOperationAndRuntimeEvidenceFailClosed(): void
    {
        $profile = Fixture::profile();
        $operation = Fixture::operation($profile->source());
        $otherOperation = Fixture::operation(Fixture::source('other'), identity: 'different-operation-identity');

        $this->expectException(NetworkEgressRuntimeEvidenceException::class);
        (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest(
            $profile,
            Fixture::wiring($profile),
            $operation,
            Fixture::runtime($otherOperation),
        ));
    }

    public function testRuntimeCountersMustRemainWithinTheExactOperationBudget(): void
    {
        $profile = Fixture::profile();
        $operation = Fixture::operation($profile->source());
        $valid = Fixture::runtime($operation);
        $overBudget = new EgressRuntimeResult(
            $valid->operationResult(),
            $operation->budget()->totalTimeoutMilliseconds() + 1,
            $valid->resolutionCandidateCount(),
            $valid->progressEmissionCount(),
            $valid->peakBufferedBytes(),
            $valid->finalRetainedEvidenceBytes(),
        );

        $this->expectException(NetworkEgressRuntimeEvidenceException::class);
        (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest(
            $profile,
            Fixture::wiring($profile),
            $operation,
            $overBudget,
        ));
    }

    #[DataProvider('outcomes')]
    public function testAllSixOutcomesRemainExactWithoutReplayAuthorization(EgressOutcome $outcome): void
    {
        $profile = Fixture::profile();
        $operation = Fixture::operation($profile->source());
        $inspection = (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest(
            $profile,
            Fixture::wiring($profile),
            $operation,
            Fixture::runtime($operation, $outcome),
        ));

        self::assertSame($outcome, $inspection->runtimeOutcome());
        self::assertTrue($inspection->cleanupComplete());
        $limitations = $inspection->toArray()['limitations'];
        self::assertIsArray($limitations);
        self::assertSame('not-performed', $limitations['replay']);
        self::assertSame('not-established', $limitations['network_effect']);
        if (in_array($outcome, [EgressOutcome::CancelledAfterPossibleEffect, EgressOutcome::UncertainEffect], true)) {
            self::assertContains($inspection->runtimeOutcome()->value, ['cancelled_after_possible_effect', 'uncertain_effect']);
            self::assertTrue($inspection->failurePresent());
        }
    }

    /** @return list<array{EgressOutcome}> */
    public static function outcomes(): array
    {
        return array_map(static fn(EgressOutcome $outcome): array => [$outcome], EgressOutcome::cases());
    }
}
