<?php

declare(strict_types=1);

namespace Kongono\Observability\Tests\Unit;

use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\Exception\InvalidObservabilityValueException;
use Kongono\Observability\SignalAttribute;
use PHPUnit\Framework\TestCase;

final class AttributeSetTest extends TestCase
{
    public function testEmptyAndMaximumSetsAreBoundedCanonicalAndQueryable(): void
    {
        $empty = new AttributeSet([]);
        self::assertSame([], $empty->attributes());
        self::assertSame(0, $empty->count());
        self::assertTrue($empty->isEmpty());
        self::assertNull($empty->find(new AttributeKey('missing')));

        $input = [];
        for ($index = 31; $index >= 0; --$index) {
            $input[] = new SignalAttribute(new AttributeKey(sprintf('key.%02d', $index)), AttributeValue::integer($index));
        }
        $set = new AttributeSet($input);
        self::assertSame(32, $set->count());
        self::assertFalse($set->isEmpty());
        self::assertSame(
            array_map(static fn(SignalAttribute $attribute): string => $attribute->key()->value(), array_reverse($input)),
            array_map(static fn(SignalAttribute $attribute): string => $attribute->key()->value(), $set->attributes()),
        );
        self::assertSame($input[21]->value(), $set->find(new AttributeKey('key.10')));
    }

    public function testSetPreservesObjectsButNotInputOrReturnedArrayAliases(): void
    {
        $z = new SignalAttribute(new AttributeKey('z'), AttributeValue::text('last'));
        $a = new SignalAttribute(new AttributeKey('a'), AttributeValue::text('first'));
        $input = [$z, $a];
        $set = new AttributeSet($input);
        $input[] = new SignalAttribute(new AttributeKey('later'), AttributeValue::text('ignored'));

        $first = $set->attributes();
        self::assertSame([$a, $z], $first);
        $first[] = new SignalAttribute(new AttributeKey('mutated'), AttributeValue::text('copy'));
        self::assertSame([$a, $z], $set->attributes());
        self::assertNotSame($first, $set->attributes());
    }

    public function testInvalidShapeMemberDuplicateAndCountAreRejected(): void
    {
        $valid = new SignalAttribute(new AttributeKey('a'), AttributeValue::integer(1));
        $invalidSets = [
            [1 => $valid],
            [$valid, 'not-an-attribute'],
            [$valid, new SignalAttribute(new AttributeKey('a'), AttributeValue::integer(2))],
            array_map(
                static fn(int $index): SignalAttribute => new SignalAttribute(new AttributeKey(sprintf('key.%02d', $index)), AttributeValue::integer($index)),
                range(0, 32),
            ),
        ];

        foreach ($invalidSets as $invalid) {
            try {
                new AttributeSet($invalid);
                self::fail('Invalid attribute set was accepted.');
            } catch (InvalidObservabilityValueException $exception) {
                self::assertSame('KGN-OBSERVABILITY-1002', $exception->diagnosticId());
            }
        }
    }
}
