<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit;

use Kongono\Tracing\Exception\InvalidTracingValueException;
use Kongono\Tracing\TraceId;
use PHPUnit\Framework\TestCase;

final class TraceIdTest extends TestCase
{
    public function testExactValidBytesAndEqualityArePreserved(): void
    {
        $value = '0123456789abcdef0123456789abcdef';
        $identifier = new TraceId($value);

        self::assertSame($value, $identifier->value());
        self::assertTrue($identifier->equals(new TraceId($value)));
        self::assertFalse($identifier->equals(new TraceId('1123456789abcdef0123456789abcdef')));
    }

    public function testInvalidBoundariesAreRejectedWithoutDisclosure(): void
    {
        foreach (['', str_repeat('0', 32), str_repeat('a', 31), str_repeat('a', 33), str_repeat('A', 32), str_repeat('g', 32), ' ' . str_repeat('a', 32)] as $value) {
            try {
                new TraceId($value);
                self::fail('Invalid trace identifier was accepted.');
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
