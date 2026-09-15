<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Source;

use Kongono\AssetBuild\Exception\InvalidAssetSourceException;
use Kongono\AssetBuild\Source\AssetSourcePath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetSourcePathTest extends TestCase
{
    public function testCanonicalPathAndEquality(): void
    {
        $path = new AssetSourcePath('assets/app.min.js');
        self::assertSame('assets/app.min.js', $path->value());
        self::assertTrue($path->equals(new AssetSourcePath('assets/app.min.js')));
        $maximumBytes = implode('/', [str_repeat('a', 128), str_repeat('b', 128), str_repeat('c', 128), str_repeat('d', 125)]);
        self::assertSame(512, strlen((new AssetSourcePath($maximumBytes))->value()));
        self::assertCount(64, explode('/', (new AssetSourcePath(implode('/', array_fill(0, 64, 'a'))))->value()));
    }

    #[DataProvider('invalidPaths')]
    public function testInvalidPathsFailWithoutDisclosure(string $value): void
    {
        try {
            new AssetSourcePath($value);
            self::fail('Expected failure.');
        } catch (InvalidAssetSourceException $exception) {
            if ('' !== $value) {
                self::assertStringNotContainsString($value, (string) $exception);
            }
            self::assertSame('KGN-ASSET-BUILD-1911', $exception->diagnosticId());
        }
    }

    /** @return iterable<array{string}> */
    public static function invalidPaths(): iterable
    {
        foreach (['', '/abs', '//unc/share', 'C:/drive', 'a\\b', 'A/b', 'a//b', 'a/./b', 'a/../b', 'a.', 'a ', 'a:b', "a\tb", "a\0b", str_repeat('a', 129), str_repeat('a', 513), implode('/', array_fill(0, 65, 'a'))] as $value) {
            yield [$value];
        }
        foreach (['con', 'prn', 'aux', 'nul', 'clock$', 'com1', 'com2', 'com3', 'com4', 'com5', 'com6', 'com7', 'com8', 'com9', 'lpt1', 'lpt2', 'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9'] as $device) {
            yield [$device];
            yield [$device . '.txt'];
        }
    }
}
