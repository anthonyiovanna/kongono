<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Exception;

use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressCurl\Exception\CurlTransportOperationException;
use Kongono\NetworkEgressCurl\Exception\ExceptionInterface;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NetworkEgressCurlExceptionTest extends TestCase
{
    public function testStringificationIsDeterministicAndPreviousIdentityIsPreserved(): void
    {
        $invalid = InvalidCurlTransportProfileException::invalid('ca_bundle_shape');
        self::assertInstanceOf(ExceptionInterface::class, $invalid);
        self::assertSame('KGN-EGRESS-CURL-1530', $invalid->diagnosticId());
        self::assertSame($invalid::class . ': [KGN-EGRESS-CURL-1530] cURL egress transport profile is invalid.', (string) $invalid);
        self::assertLessThanOrEqual(256, strlen((string) $invalid));

        $previous = new RuntimeException('foreign secret');
        $operation = CurlTransportOperationException::transfer(EgressFailureKind::Connection, 7, $previous);
        self::assertSame($previous, $operation->getPrevious());
        self::assertStringNotContainsString('foreign secret', (string) $operation);
    }
}
