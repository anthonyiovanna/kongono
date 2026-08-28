<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit;

use Kongono\Tracing\Exception\InvalidTracingValueException;
use Kongono\Tracing\SpanId;
use PHPUnit\Framework\TestCase;

final class SpanIdTest extends TestCase
{
    public function testExactValidBytesAndEqualityArePreserved(): void
    {
        $value = '0123456789abcdef';
        $identifier = new SpanId($value);

        self::assertSame($value, $identifier->value());
        self::assertTrue($identifier->equals(new SpanId($value)));
        self::assertFalse($identifier->equals(new SpanId('1123456789abcdef')));
    }

    public function testInvalidBoundariesAreRejectedWithoutDisclosure(): void
    {
        foreach (['', str_repeat('0', 16), str_repeat('a', 15), str_repeat('a', 17), str_repeat('A', 16), str_repeat('g', 16), "\xC3\x28" . str_repeat('a', 14)] as $value) {
            try {
                new SpanId($value);
                self::fail('Invalid span identifier was accepted.');
            } catch (InvalidTracingValueException $exception) {
                self::assertSame('KGN-TRACING-1001', $exception->diagnosticId());
                if ('' !== $value) {
                    self::assertStringNotContainsString($value, $exception->getMessage());
                    self::assertStringNotContainsString($value, (string) $exception);
                }
            }
        }
    }
}
