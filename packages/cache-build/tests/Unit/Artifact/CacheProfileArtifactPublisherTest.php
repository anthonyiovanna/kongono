<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Artifact;

use Kongono\CacheBuild\Artifact\CacheProfileArtifactPublisher;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Exception\CacheProfilePublicationException;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use PHPUnit\Framework\TestCase;

final class CacheProfileArtifactPublisherTest extends TestCase
{
    public function testPublicationIsContentAddressedAndExactIdempotent(): void
    {
        $root = self::temporaryRoot();
        try {
            $artifact = (new CacheProfileBuilder())->build(new CacheProfileSet([]))->artifact();
            $publisher = new CacheProfileArtifactPublisher();
            $first = $publisher->publish($artifact, $root);
            self::assertFalse($first->identicalContentExisted());
            self::assertSame('objects/sha256/' . substr($artifact->artifactIdentity()->value(), 0, 2) . '/' . $artifact->artifactIdentity()->value(), $first->relativeDirectory());
            self::assertSame($artifact->bytes(), file_get_contents($root . '/' . $first->relativeArtifactPath()));
            self::assertTrue($publisher->publish($artifact, $root)->identicalContentExisted());

            file_put_contents($root . '/' . $first->relativeDirectory() . '/extra', 'conflict');
            $this->expectException(CacheProfilePublicationException::class);
            $publisher->publish($artifact, $root);
        } finally {
            self::remove($root);
        }
    }

    private static function temporaryRoot(): string
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-cache-build-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root, 0700));

        return $root;
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
