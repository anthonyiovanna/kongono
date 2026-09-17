<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Integration;

use Kongono\CacheBuild\Artifact\CacheProfileArtifactPublisher;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use PHPUnit\Framework\TestCase;

final class CacheProfilePublicationIsolationTest extends TestCase
{
    public function testSequentialRootsDoNotSharePublicationState(): void
    {
        $base = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-cache-isolation-' . bin2hex(random_bytes(8));
        $left = $base . '/left';
        $right = $base . '/right';
        self::assertTrue(mkdir($left, 0700, true));
        self::assertTrue(mkdir($right, 0700, true));
        try {
            $artifact = (new CacheProfileBuilder())->build(new CacheProfileSet([]))->artifact();
            $publisher = new CacheProfileArtifactPublisher();
            $leftEvidence = $publisher->publish($artifact, $left);
            $rightEvidence = $publisher->publish($artifact, $right);
            self::assertFalse($leftEvidence->identicalContentExisted());
            self::assertFalse($rightEvidence->identicalContentExisted());
            self::assertSame($leftEvidence->relativeArtifactPath(), $rightEvidence->relativeArtifactPath());
        } finally {
            self::remove($base);
        }
    }

    private static function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $entries = scandir($path);
        foreach (false === $entries ? [] : $entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $child = $path . '/' . $entry;
            is_dir($child) ? self::remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
