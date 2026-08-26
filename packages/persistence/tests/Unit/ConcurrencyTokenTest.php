<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use JsonSerializable;
use Kongono\Persistence\ConcurrencyToken;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stringable;

final class ConcurrencyTokenTest extends TestCase
{
    public function testTokenIsExactOpaqueAndComparable(): void
    {
        $token = new ConcurrencyToken('etag.v1', 'opaque-value');

        self::assertSame('etag.v1', $token->scheme());
        self::assertSame('opaque-value', $token->value());
        self::assertTrue($token->equals(new ConcurrencyToken('etag.v1', 'opaque-value')));
        self::assertFalse($token->equals(new ConcurrencyToken('etag.v2', 'opaque-value')));

        $reflection = new ReflectionClass($token);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertFalse($reflection->implementsInterface(Stringable::class));
        self::assertFalse($reflection->implementsInterface(JsonSerializable::class));
        foreach (['__toString', '__serialize', '__unserialize', '__debugInfo', '__get', '__set'] as $method) {
            self::assertFalse($reflection->hasMethod($method));
        }
    }

    #[DataProvider('invalidSchemes')]
    public function testInvalidSchemesAreRejected(string $scheme): void
    {
        $this->expectException(InvalidPersistenceValueException::class);
        new ConcurrencyToken($scheme, 'opaque');
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSchemes(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['ETag'];
        yield 'leading digit' => ['1etag'];
        yield 'space' => ['etag v1'];
        yield 'over bound' => ['a' . str_repeat('b', 64)];
    }

    #[DataProvider('invalidValues')]
    public function testInvalidOpaqueValuesAreRejectedWithoutDisclosure(string $value): void
    {
        try {
            new ConcurrencyToken('etag', $value);
            self::fail('Invalid token value was accepted.');
        } catch (InvalidPersistenceValueException $exception) {
            self::assertSame('KGN-PERSISTENCE-1001', $exception->diagnosticId());
            if ('' !== $value) {
                self::assertStringNotContainsString($value, $exception->getMessage());
                self::assertStringNotContainsString($value, (string) $exception);
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        yield 'empty' => [''];
        yield 'over bound' => [str_repeat('v', 513)];
        yield 'invalid utf8' => ["\xC3\x28"];
        yield 'nul' => ["value\0suffix"];
        yield 'c0' => ["value\x1Fsuffix"];
        yield 'delete' => ["value\x7Fsuffix"];
        yield 'c1' => ["value\u{009F}suffix"];
    }
}
