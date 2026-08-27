<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess\Tests\Unit;

use JsonSerializable;
use Kongono\IdentityAccess\Exception\InvalidIdentityAccessValueException;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stringable;

final class ResourceReferenceTest extends TestCase
{
    public function testResourceReferenceAccessorsAndEqualityAreExact(): void
    {
        $type = new ResourceType('invoice.record');
        $id = new ResourceId('Tenant-1 / Invoice-7');
        $reference = new ResourceReference($type, $id);

        self::assertSame($type, $reference->type());
        self::assertSame($id, $reference->id());
        self::assertTrue($reference->equals(new ResourceReference(new ResourceType('invoice.record'), new ResourceId('Tenant-1 / Invoice-7'))));
        self::assertFalse($reference->equals(new ResourceReference(new ResourceType('invoice'), new ResourceId('Tenant-1 / Invoice-7'))));
        self::assertFalse($reference->equals(new ResourceReference(new ResourceType('invoice.record'), new ResourceId('tenant-1 / invoice-7'))));
    }

    #[DataProvider('invalidOpaqueIds')]
    public function testResourceIdRejectsBoundsUtf8AndControlsWithoutDisclosure(string $value): void
    {
        try {
            new ResourceId($value);
            self::fail('Invalid resource identifier was accepted.');
        } catch (InvalidIdentityAccessValueException $exception) {
            self::assertSame('KGN-ACCESS-1004', $exception->diagnosticId());
            if ('' !== $value && 1 === preg_match('//u', $value)) {
                self::assertStringNotContainsString($value, $exception->getMessage());
                self::assertStringNotContainsString($value, (string) $exception);
            }
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidOpaqueIds(): iterable
    {
        yield 'empty' => [''];
        yield 'over bound' => [str_repeat('r', 513)];
        yield 'invalid utf8' => ["\xC3\x28"];
        yield 'nul' => ["resource\0value"];
        yield 'c0' => ["resource\x1Fvalue"];
        yield 'delete' => ["resource\x7Fvalue"];
        yield 'c1' => ["resource\u{0080}value"];
    }

    public function testResourceIdPreservesValidBoundariesWithoutLookupOrMagic(): void
    {
        foreach (['x', '  Exact Mixed Case  ', 'resource-π-人', str_repeat('r', 512)] as $value) {
            $id = new ResourceId($value);
            self::assertSame($value, $id->value());
            self::assertTrue($id->equals(new ResourceId($value)));
        }

        $calls = 0;
        $loader = static function (string $_class) use (&$calls): void {
            ++$calls;
        };
        spl_autoload_register($loader);
        try {
            $reference = new ResourceReference(new ResourceType('synthetic.resource'), new ResourceId('Synthetic\\NeverLoadedResource'));
            self::assertSame('Synthetic\\NeverLoadedResource', $reference->id()->value());
            self::assertSame(0, $calls);
        } finally {
            spl_autoload_unregister($loader);
        }

        foreach ([ResourceId::class, ResourceReference::class] as $class) {
            $reflection = new ReflectionClass($class);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->isReadOnly());
            self::assertFalse($reflection->implementsInterface(Stringable::class));
            self::assertFalse($reflection->implementsInterface(JsonSerializable::class));
            foreach (['__toString', '__serialize', '__unserialize', '__debugInfo', '__get', '__set'] as $method) {
                self::assertFalse($reflection->hasMethod($method));
            }
        }
    }
}
