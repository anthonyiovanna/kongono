<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandIdentifier;
use Kongono\Console\Exception\InvalidConsoleValueException;
use PHPUnit\Framework\TestCase;

final class CommandIdentifierTest extends TestCase
{
    public function testCanonicalIdentifiersExposeValueAndOrdinalEquality(): void
    {
        $identifier = new CommandIdentifier('database:migrate-plan');

        self::assertSame('database:migrate-plan', $identifier->value());
        self::assertTrue($identifier->equals(new CommandIdentifier('database:migrate-plan')));
        self::assertFalse($identifier->equals(new CommandIdentifier('database:migrate')));
        self::assertSame(str_repeat('a', 128), (new CommandIdentifier(str_repeat('a', 128)))->value());
    }

    public function testInvalidIdentifiersAreRejectedWithoutDisclosure(): void
    {
        $invalid = ['', 'A', '1command', '-command', 'command-', 'command--run', 'command::run', 'command:Run', "command run", "command\0run", 'café', str_repeat('a', 129)];

        foreach ($invalid as $value) {
            try {
                new CommandIdentifier($value);
                self::fail('Expected an invalid identifier to be rejected.');
            } catch (InvalidConsoleValueException $exception) {
                self::assertSame('KGN-CONSOLE-1001', $exception->diagnosticId());
                if ('' !== $value) {
                    self::assertStringNotContainsString($value, $exception->getMessage());
                    self::assertStringNotContainsString($value, (string) $exception);
                }
                foreach ($exception->diagnosticContext()->fields() as $field) {
                    self::assertIsString($field->value());
                    if ('' !== $value) {
                        self::assertStringNotContainsString($value, $field->value());
                    }
                }
            }
        }
    }
}
