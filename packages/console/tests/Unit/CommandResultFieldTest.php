<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandResultField;
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultFieldLabel;
use Kongono\Console\CommandResultFieldName;
use Kongono\Console\CommandResultValue;
use Kongono\Console\Exception\InvalidConsoleValueException;
use PHPUnit\Framework\TestCase;

final class CommandResultFieldTest extends TestCase
{
    public function testFieldPreservesExactTupleAndBoundaries(): void
    {
        $name = new CommandResultFieldName('a' . str_repeat('1', 63));
        $label = new CommandResultFieldLabel(str_repeat('é', 40));
        $value = CommandResultValue::boolean(true);
        $field = new CommandResultField($name, $label, $value, CommandResultFieldDisclosure::Protected);

        self::assertSame($name, $field->name());
        self::assertSame($label, $field->label());
        self::assertSame($value, $field->value());
        self::assertSame(CommandResultFieldDisclosure::Protected, $field->disclosure());
        self::assertSame(['safe', 'protected'], array_column(CommandResultFieldDisclosure::cases(), 'value'));
    }

    public function testInvalidNamesAndLabelsAreRejectedWithoutRetention(): void
    {
        foreach (['', 'A', '1name', 'name-', 'name__part', 'name part', str_repeat('a', 65)] as $raw) {
            $this->assertRejected(static fn(): CommandResultFieldName => new CommandResultFieldName($raw), $raw);
        }
        foreach (['', ' leading', 'trailing ', "line\nbreak", "tab\tvalue", "\u{0085}", "\xC3\x28", "\xEF\xBB\xBFlabel", str_repeat('x', 81)] as $raw) {
            $this->assertRejected(static fn(): CommandResultFieldLabel => new CommandResultFieldLabel($raw), $raw);
        }
    }

    /** @param callable(): object $operation */
    private function assertRejected(callable $operation, string $raw): void
    {
        try {
            $operation();
            self::fail('Expected rejection.');
        } catch (InvalidConsoleValueException $exception) {
            self::assertSame('KGN-CONSOLE-1004', $exception->diagnosticId());
            if ('' !== $raw) {
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }
}
