<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Unit\Failure;

use Kongono\ExternalServiceCredentialRuntime\Exception\InvalidCredentialLeaseRuntimeException;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailure;
use Kongono\ExternalServiceCredentialRuntime\Failure\CredentialLeaseRuntimeFailureKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CredentialLeaseRuntimeFailureTest extends TestCase
{
    /** @return iterable<string, array{CredentialLeaseRuntimeFailureKind, string}> */
    public static function validFailures(): iterable
    {
        foreach ([
            CredentialLeaseRuntimeFailureKind::Clock->value => ['clock_failed', 'clock_negative', 'clock_regression'],
            CredentialLeaseRuntimeFailureKind::Source->value => ['source_failed'],
            CredentialLeaseRuntimeFailureKind::LeaseProtocol->value => ['lease_reference_mismatch', 'lease_purpose_mismatch', 'lease_state_invalid'],
            CredentialLeaseRuntimeFailureKind::Lifetime->value => ['lifetime_exceeded'],
            CredentialLeaseRuntimeFailureKind::Consumer->value => ['consumer_failed'],
            CredentialLeaseRuntimeFailureKind::Retirement->value => ['retirement_failed', 'retirement_state_invalid'],
        ] as $kind => $reasons) {
            foreach ($reasons as $reason) {
                yield $kind . ':' . $reason => [CredentialLeaseRuntimeFailureKind::from($kind), $reason];
            }
        }
    }

    #[DataProvider('validFailures')]
    public function testClosedKindReasonRelationsAndEquality(CredentialLeaseRuntimeFailureKind $kind, string $reason): void
    {
        $failure = new CredentialLeaseRuntimeFailure($kind, $reason);
        self::assertSame($kind, $failure->kind());
        self::assertSame($reason, $failure->reason());
        self::assertTrue($failure->equals(new CredentialLeaseRuntimeFailure($kind, $reason)));
    }

    public function testInvalidKindReasonPairFailsClosed(): void
    {
        $this->expectException(InvalidCredentialLeaseRuntimeException::class);
        new CredentialLeaseRuntimeFailure(CredentialLeaseRuntimeFailureKind::Clock, 'source_failed');
    }
}
