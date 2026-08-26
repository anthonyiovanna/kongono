<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use JsonSerializable;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stringable;

final class EntityIdentityTest extends TestCase
{
    public function testIdentityIsExactOpaqueAndComparable(): void
    {
        $identity = new EntityIdentity(\stdClass::class, 'tenant-7:record-12');

        self::assertSame(\stdClass::class, $identity->entityType());
        self::assertSame('tenant-7:record-12', $identity->key());
        self::assertTrue($identity->equals(new EntityIdentity(\stdClass::class, 'tenant-7:record-12')));
        self::assertFalse($identity->equals(new EntityIdentity(\stdClass::class, 'tenant-7:record-13')));

        $reflection = new ReflectionClass($identity);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertFalse($reflection->implementsInterface(Stringable::class));
        self::assertFalse($reflection->implementsInterface(JsonSerializable::class));
        foreach (['__toString', '__serialize', '__unserialize', '__debugInfo', '__get', '__set'] as $method) {
            self::assertFalse($reflection->hasMethod($method));
        }
    }

    #[DataProvider('invalidEntityTypes')]
    public function testInvalidEntityTypeSyntaxIsRejectedWithoutDisclosure(string $entityType): void
    {
        try {
            new EntityIdentity($entityType, 'synthetic-key');
            self::fail('Invalid entity type was accepted.');
        } catch (InvalidPersistenceValueException $exception) {
            self::assertSame('KGN-PERSISTENCE-1001', $exception->diagnosticId());
            if ('' !== $entityType) {
                self::assertStringNotContainsString($entityType, $exception->getMessage());
                self::assertStringNotContainsString($entityType, (string) $exception);
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidEntityTypes(): iterable
    {
        yield 'empty' => [''];
        yield 'leading slash' => ['\\Synthetic\\Entity'];
        yield 'empty segment' => ['Synthetic\\\\Entity'];
        yield 'trailing separator' => ['Synthetic\\Entity\\'];
        yield 'invalid first character' => ['7Synthetic'];
        yield 'invalid punctuation' => ['Synthetic-Entity'];
        yield 'over bound' => [str_repeat('A', 256)];
    }

    #[DataProvider('invalidKeys')]
    public function testInvalidOpaqueKeysAreRejected(string $key): void
    {
        $this->expectException(InvalidPersistenceValueException::class);
        new EntityIdentity(\stdClass::class, $key);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidKeys(): iterable
    {
        yield 'empty' => [''];
        yield 'over bound' => [str_repeat('k', 513)];
        yield 'invalid utf8' => ["\xC3\x28"];
        yield 'nul' => ["key\0value"];
        yield 'c0' => ["key\x1Fvalue"];
        yield 'delete' => ["key\x7Fvalue"];
        yield 'c1' => ["key\u{0080}value"];
    }

    public function testEntityTypeValidationDoesNotAutoload(): void
    {
        $calls = 0;
        $loader = static function (string $_class) use (&$calls): void {
            ++$calls;
        };
        spl_autoload_register($loader);

        try {
            $identity = new EntityIdentity('Synthetic\\NeverLoadedEntity', 'key');
            self::assertSame('Synthetic\\NeverLoadedEntity', $identity->entityType());
            self::assertSame(0, $calls);
        } finally {
            spl_autoload_unregister($loader);
        }
    }
}
