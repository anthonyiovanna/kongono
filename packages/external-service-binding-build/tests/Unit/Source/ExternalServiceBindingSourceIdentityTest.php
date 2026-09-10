<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Source;

use Kongono\ExternalServiceBindingBuild\Exception\InvalidExternalServiceBindingSourceException;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExternalServiceBindingSourceIdentityTest extends TestCase
{
    public function testIdentityAcceptsAdjacentBoundsAndExactEquality(): void
    {
        $one = new ExternalServiceBindingSourceIdentity('a');
        $maximum = new ExternalServiceBindingSourceIdentity('a' . str_repeat('b', 127));
        self::assertSame('a', $one->value());
        self::assertSame(128, strlen($maximum->value()));
        self::assertTrue($one->equals(new ExternalServiceBindingSourceIdentity('a')));
        self::assertFalse($one->equals(new ExternalServiceBindingSourceIdentity('b')));
    }

    #[DataProvider('invalidIdentities')]
    public function testInvalidIdentityIsRejectedWithoutDisclosure(string $value): void
    {
        try {
            new ExternalServiceBindingSourceIdentity($value);
            self::fail('Expected identity rejection.');
        } catch (InvalidExternalServiceBindingSourceException $failure) {
            if ('' !== $value) {
                self::assertStringNotContainsString($value, $failure->getMessage());
            }
            self::assertSame('KGN-EXTERNAL-SERVICE-BINDING-BUILD-1609', $failure->diagnosticId());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIdentities(): iterable
    {
        yield 'empty' => [''];
        yield 'uppercase' => ['Upper'];
        yield 'leading punctuation' => ['.alpha'];
        yield 'too long' => ['a' . str_repeat('b', 128)];
    }
}
