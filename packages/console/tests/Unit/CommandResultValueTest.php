<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandResultValue;
use Kongono\Console\CommandResultValueKind;
use Kongono\Console\Exception\InvalidConsoleValueException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CommandResultValueTest extends TestCase
{
    public function testFactoriesPreserveExactScalarKindsAndBoundaryText(): void
    {
        $text = CommandResultValue::text(str_repeat('é', 4096));
        self::assertSame(CommandResultValueKind::Text, $text->kind());
        self::assertSame(str_repeat('é', 4096), $text->value());
        self::assertSame(-42, CommandResultValue::integer(-42)->value());
        self::assertSame(CommandResultValueKind::Integer, CommandResultValue::integer(0)->kind());
        self::assertTrue(CommandResultValue::boolean(true)->value());
        self::assertSame(CommandResultValueKind::Boolean, CommandResultValue::boolean(false)->kind());
        self::assertSame(['text', 'integer', 'boolean'], array_column(CommandResultValueKind::cases(), 'value'));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidText(): iterable
    {
        yield 'oversized' => [str_repeat('x', 8193)];
        yield 'invalid utf8' => ["\xC3\x28"];
        yield 'nul' => ["a\0b"];
        yield 'newline' => ["a\nb"];
        yield 'c1' => ["a\u{0085}b"];
        yield 'bom' => ["\xEF\xBB\xBFtext"];
    }

    #[DataProvider('invalidText')]
    public function testInvalidTextIsRejectedWithoutDisclosure(string $raw): void
    {
        try {
            CommandResultValue::text($raw);
            self::fail('Expected invalid result text.');
        } catch (InvalidConsoleValueException $exception) {
            self::assertSame('KGN-CONSOLE-1004', $exception->diagnosticId());
            self::assertStringNotContainsString($raw, $exception->getMessage());
            self::assertStringNotContainsString($raw, (string) $exception);
        }
    }
}
