<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Tests\Unit\Source;

use Kongono\AssetApplication\Exception\InvalidAssetApplicationException;
use Kongono\AssetApplication\Exception\UnsafeAssetSourceException;
use Kongono\AssetApplication\Source\AssetSourceDefinitionLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetSourceDefinitionLoaderTest extends TestCase
{
    public function testLoadsCanonicalAndEmptySourceSets(): void
    {
        $root = sys_get_temp_dir() . '/kongono-asset-source-loader-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        self::assertSame(7, file_put_contents($root . '/app.css', "body{}\n"));
        try {
            $loaded = (new AssetSourceDefinitionLoader())->load(self::canonical($root), self::document([
                'declarations' => [['identity' => 'app.style', 'kind' => 'style', 'content_type' => 'text/css', 'source_path' => 'app.css']],
                'entry_points' => ['app.style'],
            ]));
            self::assertSame(1, $loaded->sourceCount());
            self::assertSame(1, $loaded->declarationSet()->declarationCount());
            self::assertSame(1, $loaded->declarationSet()->entryPointCount());

            $empty = (new AssetSourceDefinitionLoader())->load(self::canonical($root), self::document());
            self::assertSame(0, $empty->sourceCount());
            self::assertSame(0, $empty->declarationSet()->declarationCount());
        } finally {
            unlink($root . '/app.css');
            rmdir($root);
        }
    }

    #[DataProvider('invalidDocuments')]
    public function testRejectsMalformedDuplicateAndOpenDocuments(string $bytes): void
    {
        $this->expectException(InvalidAssetApplicationException::class);
        (new AssetSourceDefinitionLoader())->load(__DIR__, $bytes);
    }

    /** @return iterable<string,array{string}> */
    public static function invalidDocuments(): iterable
    {
        yield 'malformed' => ['{'];
        yield 'duplicate' => ['{"schema":"x","schema":"y"}'];
        yield 'open' => ['{"schema":"kongono.asset-application-source","version":1,"contract_version":1,"declarations":[],"dependencies":[],"entry_points":[],"extra":true}'];
        yield 'missing' => ['{"schema":"kongono.asset-application-source","version":1,"contract_version":1,"declarations":[],"dependencies":[]}'];
        yield 'wrong top-level type' => ['[]'];
        yield 'wrong declaration collection type' => [self::document(['declarations' => ['identity' => 'app.style']])];
        yield 'wrong scalar type' => [self::document(['version' => '1'])];
        yield 'bad asset kind' => [self::document(['declarations' => [['identity' => 'app.style', 'kind' => 'unknown', 'content_type' => 'text/css', 'source_path' => 'missing.css']]])];
        yield 'duplicate declaration identity' => [self::document(['declarations' => [
            ['identity' => 'app.style', 'kind' => 'style', 'content_type' => 'text/css', 'source_path' => 'AssetSourceDefinitionLoaderTest.php'],
            ['identity' => 'app.style', 'kind' => 'style', 'content_type' => 'text/css', 'source_path' => 'AssetSourceDefinitionLoaderTest.php'],
        ]])];
        yield 'duplicate source path' => [self::document(['declarations' => [
            ['identity' => 'app.first', 'kind' => 'style', 'content_type' => 'text/css', 'source_path' => 'AssetSourceDefinitionLoaderTest.php'],
            ['identity' => 'app.second', 'kind' => 'style', 'content_type' => 'text/css', 'source_path' => 'AssetSourceDefinitionLoaderTest.php'],
        ]])];
        yield 'duplicate dependency' => [self::document([
            'declarations' => [['identity' => 'app.style', 'kind' => 'style', 'content_type' => 'text/css', 'source_path' => 'AssetSourceDefinitionLoaderTest.php']],
            'dependencies' => [
                ['source' => 'app.style', 'target' => 'app.style', 'kind' => 'static'],
                ['source' => 'app.style', 'target' => 'app.style', 'kind' => 'static'],
            ],
        ])];
        yield 'duplicate entry point' => [self::document([
            'declarations' => [['identity' => 'app.style', 'kind' => 'style', 'content_type' => 'text/css', 'source_path' => 'AssetSourceDefinitionLoaderTest.php']],
            'entry_points' => ['app.style', 'app.style'],
        ])];
        yield 'declaration count bound' => [self::document(['declarations' => array_fill(0, 1025, ['identity' => 'app.style', 'kind' => 'style', 'content_type' => 'text/css', 'source_path' => 'missing.css'])])];
    }

    #[DataProvider('ambiguousPaths')]
    public function testRejectsAmbiguousSourcePathsBeforeReading(string $path): void
    {
        $this->expectException(InvalidAssetApplicationException::class);
        (new AssetSourceDefinitionLoader())->load(self::canonical(__DIR__), self::document(['declarations' => [[
            'identity' => 'app.style',
            'kind' => 'style',
            'content_type' => 'text/css',
            'source_path' => $path,
        ]]]));
    }

    /** @return iterable<string,array{string}> */
    public static function ambiguousPaths(): iterable
    {
        yield 'traversal' => ['../app.css'];
        yield 'dot segment' => ['./app.css'];
        yield 'absolute' => ['/app.css'];
        yield 'unc' => ['//server/share/app.css'];
        yield 'drive' => ['C:/app.css'];
        yield 'url' => ['https://example.test/app.css'];
        yield 'backslash' => ['assets\\app.css'];
        yield 'alternate stream' => ['app.css:secret'];
        yield 'empty segment' => ['assets//app.css'];
    }

    public function testMissingNamedSourceFailsAsUnsafeEvidence(): void
    {
        $this->expectException(UnsafeAssetSourceException::class);
        (new AssetSourceDefinitionLoader())->load(self::canonical(__DIR__), self::document(['declarations' => [[
            'identity' => 'app.style',
            'kind' => 'style',
            'content_type' => 'text/css',
            'source_path' => 'missing.css',
        ]]]));
    }

    /** @param array<string,mixed> $changes */
    private static function document(array $changes = []): string
    {
        return json_encode(array_replace([
            'schema' => 'kongono.asset-application-source',
            'version' => 1,
            'contract_version' => 1,
            'declarations' => [],
            'dependencies' => [],
            'entry_points' => [],
        ], $changes), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private static function canonical(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if (1 === preg_match('/\A[A-Z]:\//D', $path)) {
            $path = strtolower($path[0]) . substr($path, 1);
        }

        return $path;
    }
}
