<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use JsonSerializable;
use Kongono\Console\CommandInput;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInputSensitivity;
use Kongono\Console\CommandInputValue;
use Kongono\Console\Exception\InvalidConsoleValueException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stringable;

final class CommandInputTest extends TestCase
{
    public function testInputValuesAreExplicitOpaqueTuples(): void
    {
        $name = new CommandInputName('target-name');
        $value = new CommandInputValue('');
        $input = new CommandInput($name, $value, CommandInputSensitivity::Sensitive);

        self::assertSame('target-name', $name->value());
        self::assertSame('', $value->reveal());
        self::assertSame($name, $input->name());
        self::assertSame($value, $input->value());
        self::assertSame(CommandInputSensitivity::Sensitive, $input->sensitivity());
        self::assertSame(str_repeat('x', 8192), (new CommandInputValue(str_repeat('x', 8192)))->reveal());
        self::assertSame(['ordinary', 'sensitive'], array_column(CommandInputSensitivity::cases(), 'value'));

        foreach ([CommandInputName::class, CommandInputValue::class, CommandInput::class] as $class) {
            $reflection = new ReflectionClass($class);
            self::assertFalse($reflection->hasMethod('__toString'));
            self::assertFalse($reflection->implementsInterface(JsonSerializable::class));
            self::assertFalse($reflection->implementsInterface(Stringable::class));
        }
    }

    public function testInvalidNamesAndValuesAreRejectedWithoutRetention(): void
    {
        foreach (['', '--name', '-name', 'Name', 'name_', 'name--part', str_repeat('a', 65)] as $name) {
            $this->assertInputRejected(static fn(): CommandInputName => new CommandInputName($name), $name);
        }

        foreach ([str_repeat('x', 8193), "value\0secret", "value\u{0085}secret", "\xC3\x28"] as $value) {
            $this->assertInputRejected(static fn(): CommandInputValue => new CommandInputValue($value), $value);
        }
    }

    /** @param callable(): object $operation */
    private function assertInputRejected(callable $operation, string $raw): void
    {
        try {
            $operation();
            self::fail('Expected an invalid input to be rejected.');
        } catch (InvalidConsoleValueException $exception) {
            self::assertSame('KGN-CONSOLE-1001', $exception->diagnosticId());
            if ('' !== $raw) {
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }
}
