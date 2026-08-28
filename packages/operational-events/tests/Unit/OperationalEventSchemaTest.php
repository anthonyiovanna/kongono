<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Tests\Unit;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\SignalAttribute;
use Kongono\OperationalEvents\Exception\InvalidOperationalEventSchemaException;
use Kongono\OperationalEvents\Exception\InvalidOperationalEventValueException;
use Kongono\OperationalEvents\OperationalEventField;
use Kongono\OperationalEvents\OperationalEventSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OperationalEventSchemaTest extends TestCase
{
    public function testDomainsAndFieldsAreCanonicalAndBoundsAreExact(): void
    {
        $field = new OperationalEventField(new AttributeKey('app.value'), AttributeValueKind::Integer, DiagnosticDisclosure::Safe, true, [AttributeValue::integer(3), AttributeValue::integer(1), AttributeValue::integer(2)]);
        self::assertSame([1, 2, 3], array_map(static fn(AttributeValue $value): int|float|bool|string => $value->value(), $field->allowedValues()));
        self::assertTrue($field->matches(AttributeValue::integer(2)));
        self::assertFalse($field->matches(AttributeValue::integer(4)));
        $later = new OperationalEventField(new AttributeKey('z.value'), AttributeValueKind::Boolean, DiagnosticDisclosure::Safe, false, [AttributeValue::boolean(true)]);
        $schema = new OperationalEventSchema([$later, $field]);
        self::assertSame([$field, $later], $schema->fields());
        self::assertCount(30, (new OperationalEventSchema(array_map(static fn(int $index): OperationalEventField => new OperationalEventField(new AttributeKey(sprintf('app.key.%02d', $index)), AttributeValueKind::Integer, DiagnosticDisclosure::Safe, false, [AttributeValue::integer(1)]), range(0, 29))))->fields());
    }

    public function testSchemaAndRecordValidationFailClosed(): void
    {
        $field = new OperationalEventField(new AttributeKey('app.mode'), AttributeValueKind::Text, DiagnosticDisclosure::Safe, true, [AttributeValue::text('ready')]);
        foreach ([
            static fn() => new OperationalEventSchema([$field, $field]),
            static fn() => new OperationalEventSchema(array_map(static fn(int $index): OperationalEventField => new OperationalEventField(new AttributeKey('app.over.' . $index), AttributeValueKind::Integer, DiagnosticDisclosure::Safe, false, [AttributeValue::integer(1)]), range(0, 30))),
        ] as $invalid) {
            try {
                $invalid();
                self::fail('Expected schema failure.');
            } catch (InvalidOperationalEventSchemaException) {
                self::addToAssertionCount(1);
            }
        }
        $schema = new OperationalEventSchema([$field]);
        foreach ([
            new AttributeSet([]),
            new AttributeSet([new SignalAttribute(new AttributeKey('app.mode'), AttributeValue::text('other'))]),
            new AttributeSet([new SignalAttribute(new AttributeKey('app.mode'), AttributeValue::integer(1))]),
            new AttributeSet([new SignalAttribute(new AttributeKey('app.unknown'), AttributeValue::text('ready'))]),
        ] as $attributes) {
            try {
                $schema->validate($attributes);
                self::fail('Expected value failure.');
            } catch (InvalidOperationalEventValueException) {
                self::addToAssertionCount(1);
            }
        }
    }

    #[DataProvider('deniedKeys')]
    public function testReservedAndSensitiveKeysAreDenied(string $key): void
    {
        $this->expectException(InvalidOperationalEventSchemaException::class);
        new OperationalEventField(new AttributeKey($key), AttributeValueKind::Text, DiagnosticDisclosure::Safe, false, [AttributeValue::text('value')]);
    }

    /** @return iterable<string, array{string}> */
    public static function deniedKeys(): iterable
    {
        foreach (['event.severity', 'audit.outcome', 'app.credential', 'app.cookie', 'app.csrf', 'request.body', 'database.body', 'raw.header', 'app.password', 'policy.body', 'query.string', 'app.secret', 'app.session', 'app.stack', 'app.token', 'app.url'] as $key) {
            yield $key => [$key];
        }
    }
}
