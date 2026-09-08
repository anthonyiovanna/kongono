<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Failure;

use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use PHPUnit\Framework\TestCase;

final class EgressFailureTest extends TestCase
{
    public function testKindsReasonBoundariesAndEqualityAreExact(): void
    {
        self::assertSame(['validation', 'policy_denied', 'resolution', 'connection', 'tls', 'protocol', 'redirect', 'budget', 'cancellation', 'timeout', 'response', 'cleanup', 'uncertain_effect'], array_column(EgressFailureKind::cases(), 'value'));
        foreach (['a', str_repeat('a', 64), 'policy_denied'] as $reason) {
            $failure = new EgressFailure(EgressFailureKind::PolicyDenied, $reason);
            self::assertSame(EgressFailureKind::PolicyDenied, $failure->kind());
            self::assertSame($reason, $failure->reason());
            self::assertTrue($failure->equals(new EgressFailure(EgressFailureKind::PolicyDenied, $reason)));
        }
    }

    public function testArbitraryReasonsFailClosedWithoutDisclosure(): void
    {
        foreach (['', 'UPPER', '_leading', 'trailing_', 'two__parts', 'raw credential value', str_repeat('a', 65)] as $reason) {
            try {
                new EgressFailure(EgressFailureKind::Validation, $reason);
                self::fail('Expected reason rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-EGRESS-CONTRACT-1507', 'KGN-EGRESS-CONTRACT-1508']);
                if ('' !== $reason) {
                    self::assertStringNotContainsString($reason, (string) $exception);
                }
            }
        }
    }
}
