<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Integration;

use Kongono\AssetBuild\Artifact\AssetBuildArtifactPublisher;
use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class NativePublicationTest extends TestCase
{
    public function testPublicationEvidenceContainsOnlyRelativeNames(): void
    {
        $root = sys_get_temp_dir() . '/kongono-native-publication-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root, 0700));
        try {
            $resolved = realpath($root);
            self::assertIsString($resolved);
            $publication = (new AssetBuildArtifactPublisher())->publish((new NativeAssetBuilder())->build(BuildProbe::sourceSet()), str_replace('\\', '/', $resolved));
            self::assertStringStartsWith('kongono-assets-v1-', $publication->directoryName());
            self::assertSame('artifact.json', $publication->artifactPath());
            self::assertStringNotContainsString($root, $publication->directoryName());
        } finally {
            self::remove($root);
        }
    }

    private static function remove(string $path): void
    {
        if (is_dir($path) && !is_link($path)) {
            $entries = scandir($path);
            foreach (false === $entries ? [] : $entries as $entry) {
                if ('.' !== $entry && '..' !== $entry) {
                    self::remove($path . '/' . $entry);
                }
            }
            @rmdir($path);
        } elseif (file_exists($path) || is_link($path)) {
            @unlink($path);
        }
    }
}
