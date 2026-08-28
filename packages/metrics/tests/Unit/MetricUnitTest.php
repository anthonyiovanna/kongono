<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\Exception\InvalidMetricValueException;
use Kongono\Metrics\MetricUnit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MetricUnitTest extends TestCase
{
    public function testExactBoundedAsciiGrammarIsAccepted(): void
    {
        $unit = new MetricUnit('requests/{second}*100%_total-1.0');
        self::assertSame('requests/{second}*100%_total-1.0', $unit->value());
        self::assertTrue($unit->equals(new MetricUnit('requests/{second}*100%_total-1.0')));
        self::assertFalse($unit->equals(new MetricUnit('requests')));
        self::assertSame(63, strlen((new MetricUnit(str_repeat('a', 63)))->value()));
    }

    #[DataProvider('invalidUnits')]
    public function testInvalidUnitNeverAppearsInFailure(string $raw): void
    {
        try {
            new MetricUnit($raw);
            self::fail('Expected invalid metric unit.');
        } catch (InvalidMetricValueException $exception) {
            self::assertSame('KGN-METRICS-1001', $exception->diagnosticId());
            if ('' !== $raw) {
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidUnits(): iterable
    {
        yield 'empty' => [''];
        yield 'space' => ['request count'];
        yield 'colon' => ['request:count'];
        yield 'non ascii' => ['métric'];
        yield 'too long' => [str_repeat('a', 64)];
    }
}
