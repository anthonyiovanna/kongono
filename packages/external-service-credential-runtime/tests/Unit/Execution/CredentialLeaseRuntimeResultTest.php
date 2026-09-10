<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Unit\Execution;

use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailure;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailureKind;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CredentialLeaseRuntimeResultTest extends TestCase
{
    /** @return iterable<string, array{CredentialLeaseRuntimeOutcome, CredentialLeaseRuntimeState, ?CredentialLeaseRuntimeFailure, int, int, int, bool}> */
    public static function validRelations(): iterable
    {
        $source = new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Source, 'source_failed');
        $clock = new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Clock, 'clock_failed');
        $protocol = new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::LeaseProtocol, 'lease_state_invalid');
        $consumer = new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Consumer, 'consumer_failed');
        $retirement = new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Retirement, 'retirement_failed');
        yield 'success' => [CredentialLeaseRuntimeOutcome::Succeeded, CredentialLeaseRuntimeState::Retired, null, 1, 1, 1, true];
        yield 'clock-before-acquire' => [CredentialLeaseRuntimeOutcome::FailedBeforeAcquisition, CredentialLeaseRuntimeState::Failed, $clock, 0, 0, 0, true];
        yield 'source-attempt' => [CredentialLeaseRuntimeOutcome::FailedBeforeAcquisition, CredentialLeaseRuntimeState::Failed, $source, 1, 0, 0, true];
        yield 'before-material' => [CredentialLeaseRuntimeOutcome::FailedBeforeMaterial, CredentialLeaseRuntimeState::Retired, $protocol, 1, 0, 1, true];
        yield 'material-uncertain' => [CredentialLeaseRuntimeOutcome::MaterialUseUncertain, CredentialLeaseRuntimeState::Retired, $consumer, 1, 1, 1, true];
        yield 'retirement-before-material' => [CredentialLeaseRuntimeOutcome::RetirementUncertain, CredentialLeaseRuntimeState::Uncertain, $retirement, 1, 0, 1, false];
        yield 'retirement-after-material' => [CredentialLeaseRuntimeOutcome::RetirementUncertain, CredentialLeaseRuntimeState::Uncertain, $retirement, 1, 1, 1, false];
    }

    #[DataProvider('validRelations')]
    public function testValidTerminalRelations(
        CredentialLeaseRuntimeOutcome $outcome,
        CredentialLeaseRuntimeState $state,
        ?CredentialLeaseRuntimeFailure $failure,
        int $acquisition,
        int $material,
        int $retirement,
        bool $cleanup,
    ): void {
        $result = new CredentialLeaseRuntimeResult($outcome, $state, $failure, 7, $acquisition, $material, $retirement, $cleanup);
        self::assertSame($outcome, $result->outcome());
        self::assertSame($state, $result->state());
        self::assertSame($failure, $result->failure());
        self::assertSame(7, $result->elapsedMilliseconds());
        self::assertSame($acquisition, $result->acquisitionAttemptCount());
        self::assertSame($material, $result->materialAccessAttemptCount());
        self::assertSame($retirement, $result->retirementAttemptCount());
        self::assertSame($cleanup, $result->cleanupComplete());
    }

    /** @return iterable<string, array{int, int, int, int}> */
    public static function invalidCounts(): iterable
    {
        yield 'negative elapsed' => [-1, 1, 1, 1];
        yield 'acquisition two' => [0, 2, 1, 1];
        yield 'material negative' => [0, 1, -1, 1];
        yield 'retirement two' => [0, 1, 1, 2];
    }

    #[DataProvider('invalidCounts')]
    public function testInvalidCountsFailClosed(int $elapsed, int $acquisition, int $material, int $retirement): void
    {
        $this->expectException(InvalidCredentialLeaseRuntimeException::class);
        new CredentialLeaseRuntimeResult(CredentialLeaseRuntimeOutcome::Succeeded, CredentialLeaseRuntimeState::Retired, null, $elapsed, $acquisition, $material, $retirement, true);
    }

    public function testInvalidCrossProductFailsClosed(): void
    {
        $this->expectException(InvalidCredentialLeaseRuntimeException::class);
        new CredentialLeaseRuntimeResult(
            CredentialLeaseRuntimeOutcome::Succeeded,
            CredentialLeaseRuntimeState::Retired,
            new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Consumer, 'consumer_failed'),
            0,
            1,
            1,
            1,
            true,
        );
    }
}
