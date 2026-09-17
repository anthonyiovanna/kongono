<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Tests\Unit\Source;

use Kongono\CacheApplication\Exception\InvalidCacheApplicationException;
use Kongono\CacheApplication\Source\CacheProfileSourceLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CacheProfileSourceLoaderTest extends TestCase
{
    public function testCanonicalAndEmptySourcesBuildExactSets(): void
    {
        $loader = new CacheProfileSourceLoader();
        $set = $loader->load(self::source());
        self::assertSame(1, $set->profileCount());
        self::assertSame('application', $set->profiles()[0]->name()->value());
        self::assertSame('application-json@1', $set->profiles()[0]->codec()->canonical());
        self::assertSame(0, $loader->load("{\"schema\":\"kongono/cache-application-profile-source/1\",\"profiles\":[]}\n")->profileCount());
    }

    #[DataProvider('invalidSources')]
    public function testMalformedDuplicateUnknownMissingAndWrongTypedSourcesFailClosed(string $source): void
    {
        $this->expectException(InvalidCacheApplicationException::class);
        (new CacheProfileSourceLoader())->load($source);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSources(): iterable
    {
        yield 'malformed' => ['{'];
        yield 'duplicate' => ["{\"schema\":\"kongono/cache-application-profile-source/1\",\"schema\":\"kongono/cache-application-profile-source/1\",\"profiles\":[]}\n"];
        yield 'unknown' => ["{\"schema\":\"kongono/cache-application-profile-source/1\",\"profiles\":[],\"extra\":true}\n"];
        yield 'missing' => ["{\"schema\":\"kongono/cache-application-profile-source/1\"}\n"];
        yield 'wrong-type' => ["{\"schema\":\"kongono/cache-application-profile-source/1\",\"profiles\":{}}\n"];
        yield 'bad-version' => [str_replace('"contract_version":1', '"contract_version":2', self::source())];
    }

    private static function source(): string
    {
        return "{\"schema\":\"kongono/cache-application-profile-source/1\",\"profiles\":[{\"name\":\"application\",\"contract_version\":1,\"namespace\":\"application\",\"codec\":{\"name\":\"application-json\",\"version\":1},\"lifetime_microseconds\":1000000,\"max_value_bytes\":1024}]}\n";
    }
}
