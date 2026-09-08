<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Diagnostic;

use Kongono\NetworkEgressCurl\Diagnostic\NetworkEgressCurlDiagnostic;
use LogicException;
use PHPUnit\Framework\TestCase;

final class NetworkEgressCurlDiagnosticTest extends TestCase
{
    public function testExactClosedDiagnosticSequenceIsActionable(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-EGRESS-CURL-' . $number, range(1530, 1535)), array_column(NetworkEgressCurlDiagnostic::cases(), 'value'));
        foreach (NetworkEgressCurlDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertNotSame('', $diagnostic->context([])->operation());
        }
    }

    public function testUnsupportedContextKeyFailsWithFixedMessage(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Network egress cURL diagnostic context is unsupported.');
        NetworkEgressCurlDiagnostic::CurlTransferFailed->context(['secret' => 'hidden']);
    }
}
