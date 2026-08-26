<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Tests\Unit\Exception;

use Kongono\ConsoleCommands\Exception\ExceptionInterface;
use Kongono\ConsoleCommands\Exception\InvalidConsoleCommandsValueException;
use PHPUnit\Framework\TestCase;

final class ConsoleCommandsExceptionTest extends TestCase
{
    public function testExceptionsExposeOnlyDeterministicBoundedPresentation(): void
    {
        $family = InvalidConsoleCommandsValueException::invalidFamily();
        self::assertInstanceOf(ExceptionInterface::class, $family);
        self::assertSame('KGN-CONSOLE-COMMANDS-1001', $family->diagnosticId());
        self::assertSame('Invalid read-only command family', $family->diagnosticTitle());
        self::assertSame('KGN-CONSOLE-COMMANDS-1001: Invalid read-only command family', (string) $family);
        self::assertNull($family->getPrevious());

        $capability = InvalidConsoleCommandsValueException::invalidCapabilityState();
        self::assertSame('KGN-CONSOLE-COMMANDS-1003', $capability->diagnosticId());
        $context = json_encode($family->diagnosticContext(), JSON_THROW_ON_ERROR);
        foreach (['secret-input', 'node-key', 'service-name', 'check-reason', 'C:\\path', 'exception-text'] as $sentinel) {
            self::assertStringNotContainsString($sentinel, (string) $family . $context . (string) $capability);
        }
    }
}
