<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Artifact;

use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use PHPUnit\Framework\TestCase;

final class CacheProfileArtifactTest extends TestCase
{
    public function testArtifactExposesOnlyDetachedEvidence(): void
    {
        $artifact = (new CacheProfileBuilder())->build(new CacheProfileSet([]))->artifact();
        self::assertSame(strlen($artifact->bytes()), $artifact->byteCount());
        self::assertSame(0, $artifact->profileCount());
        self::assertTrue($artifact->profiles()->equals(new CacheProfileSet([])));
        self::assertTrue($artifact->equals($artifact));
    }
}
