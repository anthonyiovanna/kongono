<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Artifact;

use Kongono\AssetBuild\Artifact\AssetBuildArtifactPublisher;
use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Exception\AssetPublicationException;
use Kongono\AssetBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class AssetBuildArtifactPublisherTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/kongono-asset-build-test-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory, 0700));
    }

    protected function tearDown(): void
    {
        $link = $this->directory . '/linked';
        if (is_link($link)) {
            $removed = 'Windows' === PHP_OS_FAMILY ? rmdir($link) : unlink($link);
            self::assertTrue($removed);
        }
        clearstatcache(true, $link);
        self::assertFalse(is_link($link));
        self::assertFalse(file_exists($link));
        self::remove($this->directory);
        clearstatcache(true, $this->directory);
        self::assertFalse(file_exists($this->directory));
        self::assertFalse(is_link($this->directory));
    }

    public function testPublicationIsContainedAndIdempotent(): void
    {
        $result = (new NativeAssetBuilder())->build(BuildProbe::sourceSet());
        $publisher = new AssetBuildArtifactPublisher();
        $resolved = realpath($this->directory);
        self::assertIsString($resolved);
        $first = $publisher->publish($result, str_replace('\\', '/', $resolved));
        $second = $publisher->publish($result, str_replace('\\', '/', $resolved));
        self::assertSame($first->directoryName(), $second->directoryName());
        self::assertFileExists($this->directory . '/' . $first->directoryName() . '/artifact.json');
        self::assertCount(2, $first->outputPaths());
        $target = $this->directory . '/' . $first->directoryName();
        self::assertSame($result->artifact()->bytes(), file_get_contents($target . '/artifact.json'));
        foreach ($result->outputBytesByPath() as $path => $bytes) {
            self::assertSame($bytes, file_get_contents($target . '/' . $path));
        }
        $temporary = glob($this->directory . '/.kongono-assets-v1-*.tmp');
        self::assertSame([], false === $temporary ? [] : $temporary);
    }

    public function testExistingChangedExtraAndMissingTargetsAreRejectedWithoutRepair(): void
    {
        $result = (new NativeAssetBuilder())->build(BuildProbe::sourceSet());
        $publisher = new AssetBuildArtifactPublisher();
        $resolved = realpath($this->directory);
        self::assertIsString($resolved);
        $publication = $publisher->publish($result, str_replace('\\', '/', $resolved));
        $target = $this->directory . '/' . $publication->directoryName();

        $artifact = $target . '/artifact.json';
        self::assertIsInt(file_put_contents($artifact, 'changed'));
        $this->assertConflict($publisher, $result, $resolved);
        self::assertSame('changed', file_get_contents($artifact));
        self::assertTrue(unlink($artifact));
        self::assertIsInt(file_put_contents($artifact, $result->artifact()->bytes()));

        $extra = $target . '/extra.txt';
        self::assertIsInt(file_put_contents($extra, 'extra'));
        $this->assertConflict($publisher, $result, $resolved);
        self::assertSame('extra', file_get_contents($extra));
        self::assertTrue(unlink($extra));

        $output = $target . '/' . $publication->outputPaths()[0];
        self::assertTrue(unlink($output));
        $this->assertConflict($publisher, $result, $resolved);
        self::assertFileDoesNotExist($output);
        $temporary = glob($this->directory . '/.kongono-assets-v1-*.tmp');
        self::assertSame([], false === $temporary ? [] : $temporary);
    }

    public function testInvalidNoncanonicalAndUnsafeRootsFailClosedWithoutDisclosure(): void
    {
        $result = (new NativeAssetBuilder())->build(BuildProbe::sourceSet());
        $resolved = realpath($this->directory);
        self::assertIsString($resolved);
        $normalized = str_replace('\\', '/', $resolved);
        $rootOnly = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized) ? substr($normalized, 0, 3) : '/';
        foreach (['', 'relative/path', '//server/share', $rootOnly, $normalized . '/.', str_replace('/', '\\', $normalized) . '/mixed'] as $invalid) {
            try {
                (new AssetBuildArtifactPublisher())->publish($result, $invalid);
                self::fail('Expected invalid publication root failure.');
            } catch (AssetPublicationException $exception) {
                self::assertSame('KGN-ASSET-BUILD-1916', $exception->diagnosticId());
                if ('' !== $invalid) {
                    self::assertStringNotContainsString($invalid, (string) $exception);
                }
            }
        }
    }

    public function testLinkRootIsRejectedWhenPlatformCanCreateIt(): void
    {
        $target = $this->directory . '/ordinary';
        $link = $this->directory . '/linked';
        self::assertTrue(mkdir($target, 0700));
        if (!function_exists('symlink') || !@symlink($target, $link)) {
            self::markTestSkipped('The platform cannot create a directory symlink for this proof.');
        }
        $resolvedLink = str_replace('\\', '/', $link);
        $this->expectException(AssetPublicationException::class);
        (new AssetBuildArtifactPublisher())->publish((new NativeAssetBuilder())->build(BuildProbe::sourceSet()), $resolvedLink);
    }

    private function assertConflict(AssetBuildArtifactPublisher $publisher, \Kongono\AssetBuild\Build\AssetBuildResult $result, string $root): void
    {
        try {
            $publisher->publish($result, str_replace('\\', '/', $root));
            self::fail('Expected no-clobber conflict.');
        } catch (AssetPublicationException $exception) {
            self::assertSame('existing_target_conflict', $exception->diagnosticContext()->fields()[0]->value());
        }
    }

    private static function remove(string $path): void
    {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }
        if (is_dir($path) && !is_link($path)) {
            $entries = scandir($path);
            foreach (false === $entries ? [] : $entries as $entry) {
                if ('.' !== $entry && '..' !== $entry) {
                    self::remove($path . '/' . $entry);
                }
            }
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
}
