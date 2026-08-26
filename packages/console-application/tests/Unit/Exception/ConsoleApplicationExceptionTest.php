<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Exception;

use Kongono\ConsoleApplication\Exception\ExceptionInterface;
use Kongono\ConsoleApplication\Exception\InvalidConsoleApplicationValueException;
use PHPUnit\Framework\TestCase;

final class ConsoleApplicationExceptionTest extends TestCase
{
    public function testExceptionMarkerAndStringificationAreBounded(): void
    {
        $previous = new \RuntimeException('native-secret');
        $failure = InvalidConsoleApplicationValueException::applicationFailure($previous);

        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame($previous, $failure->getPrevious());
        self::assertSame('KGN-CONSOLE-APPLICATION-1003', $failure->diagnosticId());
        self::assertSame('Kongo application infrastructure failed', $failure->diagnosticTitle());
        self::assertStringContainsString('authorized boundary', $failure->diagnosticRemediation());
        self::assertSame('KGN-CONSOLE-APPLICATION-1003: Kongo application infrastructure failed', (string) $failure);
        self::assertStringNotContainsString('native-secret', (string) $failure);
    }
}
