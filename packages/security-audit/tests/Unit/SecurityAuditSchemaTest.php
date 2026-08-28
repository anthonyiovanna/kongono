<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Tests\Unit;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\SignalAttribute;
use Kongono\SecurityAudit\Exception\InvalidSecurityAuditSchemaException;
use Kongono\SecurityAudit\Exception\InvalidSecurityAuditValueException;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SecurityAuditSchemaTest extends TestCase
{
    public function testDomainAndSchemaCanonicalizationAndBoundsAreExact(): void
    {
        $field = new SecurityAuditField(new AttributeKey('app.level'), AttributeValueKind::Integer, DiagnosticDisclosure::Safe, true, [AttributeValue::integer(2), AttributeValue::integer(1)]);
        self::assertSame([1, 2], array_map(static fn(AttributeValue $value): bool|int|float|string => $value->value(), $field->allowedValues()));
        $later = new SecurityAuditField(new AttributeKey('z.level'), AttributeValueKind::Boolean, DiagnosticDisclosure::Safe, false, [AttributeValue::boolean(true)]);
        self::assertSame([$field, $later], (new SecurityAuditSchema([$later, $field]))->fields());
        self::assertCount(28, (new SecurityAuditSchema(array_map(static fn(int $index): SecurityAuditField => new SecurityAuditField(new AttributeKey(sprintf('app.key.%02d', $index)), AttributeValueKind::Integer, DiagnosticDisclosure::Safe, false, [AttributeValue::integer(1)]), range(0, 27))))->fields());
        foreach ([[], [$field, $field], array_map(static fn(int $index): SecurityAuditField => new SecurityAuditField(new AttributeKey('app.over.' . $index), AttributeValueKind::Integer, DiagnosticDisclosure::Safe, false, [AttributeValue::integer(1)]), range(0, 28))] as $invalid) {
            try {
                new SecurityAuditSchema($invalid);
                self::fail('Expected schema failure.');
            } catch (InvalidSecurityAuditSchemaException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testMissingUnknownWrongKindAndOutOfDomainValuesFail(): void
    {
        $schema = new SecurityAuditSchema([new SecurityAuditField(new AttributeKey('app.action'), AttributeValueKind::Text, DiagnosticDisclosure::Safe, true, [AttributeValue::text('read')])]);
        foreach ([
            new AttributeSet([]),
            new AttributeSet([new SignalAttribute(new AttributeKey('app.unknown'), AttributeValue::text('read'))]),
            new AttributeSet([new SignalAttribute(new AttributeKey('app.action'), AttributeValue::integer(1))]),
            new AttributeSet([new SignalAttribute(new AttributeKey('app.action'), AttributeValue::text('write'))]),
        ] as $attributes) {
            try {
                $schema->validate($attributes);
                self::fail('Expected audit value failure.');
            } catch (InvalidSecurityAuditValueException $exception) {
                self::assertStringNotContainsString('write', (string) $exception);
            }
        }
    }

    #[DataProvider('deniedKeys')]
    public function testReservedAndSensitiveKeysAreDenied(string $key): void
    {
        $this->expectException(InvalidSecurityAuditSchemaException::class);
        new SecurityAuditField(new AttributeKey($key), AttributeValueKind::Text, DiagnosticDisclosure::Safe, false, [AttributeValue::text('value')]);
    }

    /** @return iterable<string, array{string}> */
    public static function deniedKeys(): iterable
    {
        foreach (['event.severity', 'audit.outcome', 'app.credential', 'app.cookie', 'app.csrf', 'request.body', 'database.body', 'raw.header', 'app.password', 'policy.body', 'query.string', 'app.secret', 'app.session', 'app.stack', 'app.token', 'app.url'] as $key) {
            yield $key => [$key];
        }
    }
}
