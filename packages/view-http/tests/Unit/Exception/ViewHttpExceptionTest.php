<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Tests\Unit\Exception;

use Kongono\ViewHttp\Exception\ExceptionInterface;
use Kongono\ViewHttp\Exception\InvalidViewHttpPolicyException;
use Kongono\ViewHttp\Exception\ViewHttpAdaptationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewHttpExceptionTest extends TestCase
{
    public function testInvalidPolicyFailureIsNondisclosing(): void
    {
        $exception = InvalidViewHttpPolicyException::policy('header_name_grammar');
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame('KGN-VIEW-HTTP-1840', $exception->diagnosticId());
        self::assertStringNotContainsString('secret', (string) $exception);
        self::assertStringContainsString('header_name_grammar', (string) $exception);
    }

    public function testAdaptationFailureRetainsPreviousThrowable(): void
    {
        $previous = new RuntimeException('private-provider-message');
        $exception = ViewHttpAdaptationException::stream('factory_throwable', 12, $previous);
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame('KGN-VIEW-HTTP-1842', $exception->diagnosticId());
        self::assertStringNotContainsString('private-provider-message', (string) $exception);
        self::assertSame('View response stream could not be created.', $exception->getMessage());
    }

    public function testUnknownViolationIsReducedToSafeToken(): void
    {
        $exception = ViewHttpAdaptationException::assembly('secret raw value', 200, 2);
        self::assertStringContainsString('unsupported_violation', (string) $exception);
        self::assertStringNotContainsString('secret raw value', (string) $exception);
        self::assertSame('KGN-VIEW-HTTP-1844', $exception->diagnosticId());
    }
}
