<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Profile;

use Kongono\CacheBuild\Exception\InvalidCacheProfileException;
use Kongono\CacheBuild\Profile\CacheProfileName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CacheProfileNameTest extends TestCase
{
    public function testAcceptedNameIsExact(): void
    {
        $name = new CacheProfileName('session.v1-read');
        self::assertSame('session.v1-read', $name->value());
        self::assertTrue($name->equals(new CacheProfileName('session.v1-read')));
    }

    #[DataProvider('invalidNames')]
    public function testInvalidNamesFailClosed(string $value): void
    {
        $this->expectException(InvalidCacheProfileException::class);
        new CacheProfileName($value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidNames(): iterable
    {
        foreach (['empty' => '', 'uppercase' => 'Upper', 'leading' => '.lead', 'trailing' => 'trail-', 'adjacent' => 'two..parts', 'path' => 'path/name', 'colon' => 'name:value', 'too-long' => str_repeat('a', 65)] as $label => $value) {
            yield $label => [$value];
        }
    }
}
