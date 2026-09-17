<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Build;

use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use PHPUnit\Framework\TestCase;

final class CacheProfileBuilderTest extends TestCase
{
    public function testEmptySetBuildIsCanonicalAndComplete(): void
    {
        $result = (new CacheProfileBuilder())->build(new CacheProfileSet([]));
        $decoded = json_decode($result->artifact()->bytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertSame(['schema', 'source_identity', 'build_identity', 'profile_count', 'profiles'], array_keys($decoded));
        self::assertSame('kongono/cache-profile-artifact/1', $decoded['schema']);
        self::assertSame(0, $decoded['profile_count']);
        self::assertSame([], $decoded['profiles']);
        self::assertStringEndsWith("\n", $result->artifact()->bytes());
        self::assertSame($result->artifact()->artifactIdentity()->value(), hash('sha256', $result->artifact()->bytes()));
    }
}
