<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Artifact;

use Kongono\CacheBuild\Artifact\CacheProfileArtifactValidator;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Exception\InvalidCacheProfileArtifactException;
use Kongono\CacheBuild\Exception\StaleCacheProfileArtifactException;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;
use PHPUnit\Framework\TestCase;

final class CacheProfileArtifactValidatorTest extends TestCase
{
    public function testStructureAndCurrentnessAreExact(): void
    {
        $set = new CacheProfileSet([self::profile('alpha', 1000)]);
        $bytes = (new CacheProfileBuilder())->build($set)->artifact()->bytes();
        $validator = new CacheProfileArtifactValidator();
        self::assertSame($bytes, $validator->validateStructure($bytes)->bytes());
        self::assertSame($bytes, $validator->validateCurrent($set, $bytes)->bytes());

        $this->expectException(StaleCacheProfileArtifactException::class);
        $validator->validateCurrent(new CacheProfileSet([self::profile('alpha', 1001)]), $bytes);
    }

    public function testDuplicateAndNoncanonicalJsonFailClosed(): void
    {
        $bytes = (new CacheProfileBuilder())->build(new CacheProfileSet([]))->artifact()->bytes();
        $duplicate = str_replace('{"schema":', '{"schema":"kongono/cache-profile-artifact/1","schema":', $bytes);
        $this->expectException(InvalidCacheProfileArtifactException::class);
        (new CacheProfileArtifactValidator())->validateStructure($duplicate);
    }

    private static function profile(string $name, int $lifetime): CacheProfileDeclaration
    {
        return new CacheProfileDeclaration(ContractVersion::Version1, new CacheProfileName($name), new CacheNamespace($name . '-cache'), new CacheCodecIdentity('json', 1), new CacheLifetime($lifetime), new CacheProfileValueLimit(1024));
    }
}
