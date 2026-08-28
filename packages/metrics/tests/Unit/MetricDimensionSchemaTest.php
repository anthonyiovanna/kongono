<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit;

use Kongono\Metrics\Exception\InvalidMetricSchemaException;
use Kongono\Metrics\MetricDimension;
use Kongono\Metrics\MetricDimensionSchema;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeValue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MetricDimensionSchemaTest extends TestCase
{
    public function testValuesAndDimensionsUseExactCanonicalOrder(): void
    {
        $dimension = new MetricDimension(new AttributeKey('app.mode'), [
            AttributeValue::text('z'),
            AttributeValue::floatingPoint(2.5),
            AttributeValue::integer(3),
            AttributeValue::boolean(true),
            AttributeValue::boolean(false),
            AttributeValue::text('a'),
        ]);
        self::assertSame([false, true, 3, 2.5, 'a', 'z'], array_map(static fn(AttributeValue $value): bool|int|float|string => $value->value(), $dimension->allowedValues()));
        self::assertTrue($dimension->matches(AttributeValue::text('a')));
        self::assertFalse($dimension->matches(AttributeValue::text('missing')));

        $first = new MetricDimension(new AttributeKey('a.key'), [AttributeValue::integer(1)]);
        $last = new MetricDimension(new AttributeKey('z.key'), [AttributeValue::integer(1)]);
        $schema = new MetricDimensionSchema([$last, $first]);
        self::assertSame([$first, $last], $schema->dimensions());
        self::assertSame(1, $schema->seriesCapacity());
        self::assertSame(1, (new MetricDimensionSchema([]))->seriesCapacity());
    }

    #[DataProvider('deniedKeys')]
    public function testSensitiveAndReservedDimensionFamiliesAreDenied(string $key): void
    {
        $this->expectException(InvalidMetricSchemaException::class);
        new MetricDimension(new AttributeKey($key), [AttributeValue::text('value')]);
    }

    public function testDimensionAndCardinalityBoundsAreHard(): void
    {
        $eightValues = array_map(static fn(int $value): AttributeValue => AttributeValue::integer($value), range(1, 8));
        $left = new MetricDimension(new AttributeKey('app.left'), $eightValues);
        $right = new MetricDimension(new AttributeKey('app.right'), $eightValues);
        self::assertSame(64, (new MetricDimensionSchema([$left, $right]))->seriesCapacity());

        foreach ([
            static fn(): MetricDimension => new MetricDimension(new AttributeKey('app.empty'), []),
            static fn(): MetricDimension => new MetricDimension(new AttributeKey('app.duplicate'), [AttributeValue::integer(1), AttributeValue::integer(1)]),
            static fn(): MetricDimensionSchema => new MetricDimensionSchema([$left, $right, new MetricDimension(new AttributeKey('app.third'), [AttributeValue::integer(1), AttributeValue::integer(2)])]),
            static fn(): MetricDimensionSchema => new MetricDimensionSchema(array_map(static fn(int $index): MetricDimension => new MetricDimension(new AttributeKey('app.k' . $index), [AttributeValue::integer(1)]), range(0, 8))),
        ] as $invalid) {
            try {
                $invalid();
                self::fail('Expected invalid dimension schema.');
            } catch (InvalidMetricSchemaException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function deniedKeys(): iterable
    {
        foreach (['metric.dynamic', 'trace.id', 'tenant', 'tenant.id', 'principal.id', 'identity', 'user.name', 'resource.id', 'session.id', 'credential.kind', 'secret.name', 'token.kind', 'csrf.value', 'policy.name', 'request.body', 'request.body.field', 'url.full', 'query.text', 'route.parameter.id', 'exception.type'] as $key) {
            yield $key => [$key];
        }
    }
}
