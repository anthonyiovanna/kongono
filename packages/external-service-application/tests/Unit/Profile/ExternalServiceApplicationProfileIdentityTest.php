<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Unit\Profile;

use Kongono\ExternalServiceApplication\Exception\InvalidExternalServiceApplicationProfileException;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileIdentity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExternalServiceApplicationProfileIdentityTest extends TestCase
{
    public function testLowercaseSha256IdentityIsComparable(): void
    {
        $value = str_repeat('a', 64);
        $identity = new ExternalServiceApplicationProfileIdentity($value);

        self::assertSame($value, $identity->value());
        self::assertTrue($identity->equals(new ExternalServiceApplicationProfileIdentity($value)));
        self::assertFalse($identity->equals(new ExternalServiceApplicationProfileIdentity(str_repeat('b', 64))));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIdentities(): iterable
    {
        yield 'short' => [str_repeat('a', 63)];
        yield 'uppercase' => [str_repeat('A', 64)];
        yield 'non hexadecimal' => [str_repeat('z', 64)];
    }

    #[DataProvider('invalidIdentities')]
    public function testInvalidIdentityFailsClosed(string $value): void
    {
        $this->expectException(InvalidExternalServiceApplicationProfileException::class);
        new ExternalServiceApplicationProfileIdentity($value);
    }
}
