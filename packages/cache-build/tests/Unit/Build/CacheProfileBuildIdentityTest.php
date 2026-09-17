<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Build;

use Kongono\CacheBuild\Build\CacheProfileBuildIdentity;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use PHPUnit\Framework\TestCase;

final class CacheProfileBuildIdentityTest extends TestCase
{
    public function testIdentityIsDeterministicAndDomainSeparated(): void
    {
        $source = (new CacheProfileSet([]))->identity();
        $identity = CacheProfileBuildIdentity::fromSourceIdentity($source);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $identity->value());
        self::assertNotSame($source->value(), $identity->value());
        self::assertTrue($identity->equals(CacheProfileBuildIdentity::fromSourceIdentity($source)));
    }
}
