<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Exception;

use Kongono\NetworkEgressContract\Exception\ExceptionInterface;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NetworkEgressContractExceptionTest extends TestCase
{
    public function testFactoriesPreservePreviousIdentityAndNeverDiscloseProtectedInput(): void
    {
        $raw = 'raw://user:credential@host.invalid/private?token=value#fragment';
        $previous = new RuntimeException($raw);
        $exceptions = [
            [InvalidNetworkEgressContractException::invalidIdentity($raw, $previous), 'KGN-EGRESS-CONTRACT-1501'],
            [InvalidNetworkEgressContractException::invalidOrigin($raw, $previous), 'KGN-EGRESS-CONTRACT-1502'],
            [InvalidNetworkEgressContractException::invalidReference($raw, $previous), 'KGN-EGRESS-CONTRACT-1503'],
            [InvalidNetworkEgressContractException::invalidBudget($raw, -1, $previous), 'KGN-EGRESS-CONTRACT-1504'],
            [InvalidNetworkEgressContractException::invalidCapabilityEvidence($raw, -1, -2, $previous), 'KGN-EGRESS-CONTRACT-1505'],
            [InvalidNetworkEgressContractException::invalidOperation($raw, $previous), 'KGN-EGRESS-CONTRACT-1506'],
            [InvalidNetworkEgressContractException::invalidOutcomeOrReceipt($raw, $raw, $raw, $previous), 'KGN-EGRESS-CONTRACT-1507'],
            [InvalidNetworkEgressContractException::contractLimitExceeded($raw, -1, -2, $previous), 'KGN-EGRESS-CONTRACT-1508'],
        ];
        foreach ($exceptions as [$exception, $id]) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($id, $exception->diagnosticId());
            self::assertSame($previous, $exception->getPrevious());
            self::assertSame(0, $exception->getCode());
            self::assertStringNotContainsString($raw, $exception->getMessage());
            self::assertStringNotContainsString($raw, (string) $exception);
            foreach ($exception->diagnosticContext()->fields() as $field) {
                self::assertStringNotContainsString($raw, (string) $field->value());
            }
        }
    }
}
