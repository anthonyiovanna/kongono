<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Profile;

use Kongono\CacheBuild\Exception\InvalidCacheProfileException;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;
use PHPUnit\Framework\TestCase;

final class CacheProfileSetTest extends TestCase
{
    public function testSetIsDetachedOrderedAndInputOrderIndependent(): void
    {
        $a = self::profile('alpha', 'alpha-cache');
        $z = self::profile('zeta', 'zeta-cache');
        $input = [$z, $a];
        $set = new CacheProfileSet($input);
        $input[0] = $a;
        self::assertSame(['alpha', 'zeta'], array_map(static fn(CacheProfileDeclaration $profile): string => $profile->name()->value(), $set->profiles()));
        self::assertSame($set->identity()->value(), (new CacheProfileSet([$a, $z]))->identity()->value());
        self::assertSame($a, $set->profile(new CacheProfileName('alpha')));
        self::assertNull($set->profile(new CacheProfileName('missing')));
        self::assertSame(0, (new CacheProfileSet([]))->profileCount());
    }

    public function testDuplicateNamespaceFailsClosed(): void
    {
        $this->expectException(InvalidCacheProfileException::class);
        new CacheProfileSet([self::profile('one', 'shared'), self::profile('two', 'shared')]);
    }

    private static function profile(string $name, string $namespace): CacheProfileDeclaration
    {
        return new CacheProfileDeclaration(ContractVersion::Version1, new CacheProfileName($name), new CacheNamespace($namespace), new CacheCodecIdentity('json', 1), new CacheLifetime(1000), new CacheProfileValueLimit(1024));
    }
}
