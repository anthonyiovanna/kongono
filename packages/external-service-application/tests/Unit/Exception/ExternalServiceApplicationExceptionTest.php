<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Unit\Exception;

use Kongono\ExternalServiceApplication\Exception\ExternalServiceApplicationOperationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExternalServiceApplicationExceptionTest extends TestCase
{
    public function testForeignThrowableIdentityIsPreservedButPresentationIsSafe(): void
    {
        $secret = 'foreign-secret-message-7fbc0d';
        $foreign = new RuntimeException($secret, 91);
        $failure = ExternalServiceApplicationOperationException::failed('request_factory', $foreign);

        self::assertSame($foreign, $failure->getPrevious());
        self::assertNull($failure->egressResult());
        self::assertStringNotContainsString($secret, $failure->getMessage());
        self::assertStringNotContainsString($secret, (string) $failure);
        self::assertStringNotContainsString('91', (string) $failure);
        self::assertSame('request_factory', $failure->diagnosticContext()->fields()[0]->value());
    }
}
