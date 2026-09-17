<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Integration;

use Fiber;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;
use PHPUnit\Framework\TestCase;

final class CacheProfileDeterminismTest extends TestCase
{
    public function testReverseRepeatedAndFiberBuildsAreIdenticalAndIsolated(): void
    {
        $a = self::profile('alpha');
        $z = self::profile('zeta');
        $builder = new CacheProfileBuilder();
        $forward = $builder->build(new CacheProfileSet([$a, $z]));
        $reverse = $builder->build(new CacheProfileSet([$z, $a]));
        self::assertTrue($forward->equals($reverse));
        self::assertTrue($forward->equals($builder->build(new CacheProfileSet([$a, $z]))));

        $fiber = new Fiber(static fn(): string => (new CacheProfileBuilder())->build(new CacheProfileSet([$z, $a]))->artifact()->bytes());
        $fiber->start();
        self::assertSame($forward->artifact()->bytes(), $fiber->getReturn());
        self::assertSame($forward->artifact()->bytes(), $builder->build(new CacheProfileSet([$a, $z]))->artifact()->bytes());
    }

    private static function profile(string $name): CacheProfileDeclaration
    {
        return new CacheProfileDeclaration(ContractVersion::Version1, new CacheProfileName($name), new CacheNamespace($name . '-cache'), new CacheCodecIdentity('json', 1), new CacheLifetime(1000), new CacheProfileValueLimit(1024));
    }
}
