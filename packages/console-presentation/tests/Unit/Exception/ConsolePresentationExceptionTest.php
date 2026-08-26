<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Tests\Unit\Exception;

use Kongono\ConsolePresentation\Exception\CommandOutputException;
use Kongono\ConsolePresentation\Exception\ExceptionInterface;
use Kongono\ConsolePresentation\Exception\InvalidConsolePresentationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConsolePresentationExceptionTest extends TestCase
{
    public function testFactoriesExposeExactSafeDiagnosticsAndMarker(): void
    {
        $cases = [
            [InvalidConsolePresentationException::invalidValue(), 'KGN-CONSOLE-PRESENTATION-1001'],
            [InvalidConsolePresentationException::unsupportedResultData(), 'KGN-CONSOLE-PRESENTATION-1002'],
            [InvalidConsolePresentationException::renderingFailed(), 'KGN-CONSOLE-PRESENTATION-1003'],
            [InvalidConsolePresentationException::invalidHelp(), 'KGN-CONSOLE-PRESENTATION-1005'],
        ];
        foreach ($cases as [$exception, $identifier]) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($identifier, $exception->diagnosticId());
            self::assertStringNotContainsString('secret', (string) $exception);
        }
    }

    public function testOutputExceptionRetainsPreviousWithoutPresentingIt(): void
    {
        $previous = new RuntimeException('secret-port-value');
        $exception = CommandOutputException::fromPrevious($previous);
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame('KGN-CONSOLE-PRESENTATION-1004', $exception->diagnosticId());
        self::assertStringNotContainsString('secret-port-value', $exception->getMessage());
        self::assertStringNotContainsString('secret-port-value', (string) $exception);
    }
}
