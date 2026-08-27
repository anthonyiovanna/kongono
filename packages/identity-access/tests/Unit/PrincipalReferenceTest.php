<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess\Tests\Unit;

use JsonSerializable;
use Kongono\IdentityAccess\Exception\InvalidIdentityAccessValueException;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stringable;

final class PrincipalReferenceTest extends TestCase
{
    public function testPrincipalKindsAndKindSensitiveEqualityAreExact(): void
    {
        self::assertSame(
            ['human', 'service', 'organization', 'system', 'agent'],
            array_column(PrincipalKind::cases(), 'value'),
        );

        $id = new PrincipalId('Case-Sensitive Principal');
        $human = new PrincipalReference(PrincipalKind::Human, $id);

        self::assertSame(PrincipalKind::Human, $human->kind());
        self::assertSame($id, $human->id());
        self::assertTrue($human->equals(new PrincipalReference(PrincipalKind::Human, new PrincipalId('Case-Sensitive Principal'))));
        self::assertFalse($human->equals(new PrincipalReference(PrincipalKind::Agent, new PrincipalId('Case-Sensitive Principal'))));
        self::assertFalse($human->equals(new PrincipalReference(PrincipalKind::Human, new PrincipalId('case-sensitive principal'))));
    }

    #[DataProvider('validOpaqueIds')]
    public function testPrincipalIdPreservesExactValidUtf8Bytes(string $value): void
    {
        $id = new PrincipalId($value);

        self::assertSame($value, $id->value());
        self::assertTrue($id->equals(new PrincipalId($value)));
    }

    /** @return iterable<string, array{string}> */
    public static function validOpaqueIds(): iterable
    {
        yield 'minimum' => ['x'];
        yield 'spaces remain opaque' => ['  Exact Mixed Case  '];
        yield 'unicode' => ['principal-π-人'];
        yield 'maximum bytes' => [str_repeat('p', 512)];
    }

    #[DataProvider('invalidOpaqueIds')]
    public function testPrincipalIdRejectsBoundsUtf8AndControlsWithoutDisclosure(string $value): void
    {
        try {
            new PrincipalId($value);
            self::fail('Invalid principal identifier was accepted.');
        } catch (InvalidIdentityAccessValueException $exception) {
            self::assertSame('KGN-ACCESS-1001', $exception->diagnosticId());
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
        yield 'over bound' => [str_repeat('p', 513)];
        yield 'invalid utf8' => ["\xC3\x28"];
        yield 'nul' => ["principal\0value"];
        yield 'c0' => ["principal\x1Fvalue"];
        yield 'delete' => ["principal\x7Fvalue"];
        yield 'c1' => ["principal\u{0080}value"];
    }

    public function testOpaqueIdValidationDoesNotAutoloadOrExposeMagicApis(): void
    {
        $calls = 0;
        $loader = static function (string $_class) use (&$calls): void {
            ++$calls;
        };
        spl_autoload_register($loader);

        try {
            new PrincipalId('Synthetic\\NeverLoadedPrincipal');
            self::assertSame(0, $calls);
        } finally {
            spl_autoload_unregister($loader);
        }

        foreach ([PrincipalId::class, PrincipalReference::class] as $class) {
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
