<?php

declare(strict_types=1);

namespace Kongono\Observability\Tests\Unit;

use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\Exception\InvalidObservabilityValueException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AttributeValueTest extends TestCase
{
    public function testAttributeValueKindsAndNativeFactoriesAreExact(): void
    {
        self::assertSame(['boolean', 'integer', 'floating_point', 'text'], array_column(AttributeValueKind::cases(), 'value'));

        $values = [
            [AttributeValue::boolean(false), AttributeValueKind::Boolean, false],
            [AttributeValue::boolean(true), AttributeValueKind::Boolean, true],
            [AttributeValue::integer(PHP_INT_MIN), AttributeValueKind::Integer, PHP_INT_MIN],
            [AttributeValue::integer(PHP_INT_MAX), AttributeValueKind::Integer, PHP_INT_MAX],
            [AttributeValue::floatingPoint(-1.25), AttributeValueKind::FloatingPoint, -1.25],
            [AttributeValue::text(''), AttributeValueKind::Text, ''],
            [AttributeValue::text('café'), AttributeValueKind::Text, 'café'],
        ];

        foreach ($values as [$value, $kind, $native]) {
            self::assertSame($kind, $value->kind());
            self::assertSame($native, $value->value());
        }
        self::assertTrue(AttributeValue::integer(1)->equals(AttributeValue::integer(1)));
        self::assertFalse(AttributeValue::integer(1)->equals(AttributeValue::floatingPoint(1.0)));
    }

    #[DataProvider('invalidFloatProvider')]
    public function testFloatingPointRejectsEveryNonFiniteValue(float $value): void
    {
        $this->expectException(InvalidObservabilityValueException::class);
        AttributeValue::floatingPoint($value);
    }

    /** @return iterable<string, array{float}> */
    public static function invalidFloatProvider(): iterable
    {
        yield 'nan' => [NAN];
        yield 'positive infinity' => [INF];
        yield 'negative infinity' => [-INF];
    }

    public function testTextBoundsUtf8AndControlsAreExact(): void
    {
        self::assertSame(str_repeat('a', 1024), AttributeValue::text(str_repeat('a', 1024))->value());

        foreach ([str_repeat('a', 1025), "\xC3\x28", "line\nfeed", "\u{0085}"] as $invalid) {
            try {
                AttributeValue::text($invalid);
                self::fail('Invalid text was accepted.');
            } catch (InvalidObservabilityValueException $exception) {
                self::assertSame('KGN-OBSERVABILITY-1002', $exception->diagnosticId());
                self::assertStringNotContainsString($invalid, $exception->getMessage());
                self::assertStringNotContainsString($invalid, (string) $exception);
            }
        }
    }

    public function testAttributeKeyGrammarBoundsEqualityAndNoNormalizationAreExact(): void
    {
        foreach (['a', 'a0', 'request.method', 'db_query-count', str_repeat('a', 128)] as $valid) {
            self::assertSame($valid, (new AttributeKey($valid))->value());
        }
        self::assertTrue((new AttributeKey('request.id'))->equals(new AttributeKey('request.id')));
        self::assertFalse((new AttributeKey('request.id'))->equals(new AttributeKey('request-id')));

        foreach (['', 'A', 'a..b', 'a:b', 'a b', ' rejected', 'a ', 'a_', '-a', str_repeat('a', 129), 'é'] as $invalid) {
            try {
                new AttributeKey($invalid);
                self::fail('Invalid attribute key was accepted.');
            } catch (InvalidObservabilityValueException $exception) {
                self::assertSame('KGN-OBSERVABILITY-1002', $exception->diagnosticId());
                if ('' !== $invalid) {
                    self::assertStringNotContainsString($invalid, $exception->getMessage());
                }
            }
        }
    }
}
