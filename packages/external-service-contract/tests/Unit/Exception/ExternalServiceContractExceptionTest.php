<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Exception;

use Kongono\ExternalServiceContract\Exception\ExceptionInterface;
use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExternalServiceContractExceptionTest extends TestCase
{
    public function testExceptionFamilyContextAndPreviousIdentityAreExact(): void
    {
        $previous = new RuntimeException('foreign failure text');
        $exception = InvalidExternalServiceContractException::invalidEndpoint('endpoint_base_path_grammar', $previous);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame('KGN-EXTERNAL-SERVICE-CONTRACT-1602', $exception->diagnosticId());
        self::assertStringNotContainsString('foreign failure text', $exception->getMessage());
        self::assertStringNotContainsString('foreign failure text', (string) $exception);
        self::assertStringNotContainsString('Stack trace', (string) $exception);
    }

    public function testUnrecognizedTokensAreClosedWithoutEcho(): void
    {
        $raw = 'https://user@service.test/?credential=private';
        $exception = InvalidExternalServiceContractException::invalidResult($raw, $raw, $raw);
        self::assertStringContainsString('unsupported_violation', (string) $exception);
        self::assertStringNotContainsString($raw, (string) $exception);
    }
}
