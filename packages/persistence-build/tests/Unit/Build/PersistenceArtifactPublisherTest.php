<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Build;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Build\PersistenceArtifactPublisher;
use Kongono\PersistenceBuild\Exception\PersistenceArtifactPublicationException;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

final class PersistenceArtifactPublisherTest extends TestCase
{
    private string $temporaryRoot;

    protected function setUp(): void
    {
        $this->temporaryRoot = sys_get_temp_dir() . '/kongono-persistence-build-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->temporaryRoot));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->temporaryRoot)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->temporaryRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->temporaryRoot);
    }

    public function testInitialPublicationAndIdenticalRepublishAreSafe(): void
    {
        $root = dirname(__DIR__, 5);
        $artifact = (new PersistenceArtifactBuilder())->build($root, 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json');
        $publisher = new PersistenceArtifactPublisher();
        $first = $publisher->publish($artifact, $this->temporaryRoot);
        $second = $publisher->publish($artifact, $this->temporaryRoot);

        self::assertSame($first, $second);
        self::assertSame($artifact->canonicalBytes(), file_get_contents($first));
        self::assertSame([], glob(dirname($first) . '/.kongono-persistence-*'));
    }

    public function testDifferentExistingBytesAreNeverClobbered(): void
    {
        $root = dirname(__DIR__, 5);
        $artifact = (new PersistenceArtifactBuilder())->build($root, 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json');
        $publisher = new PersistenceArtifactPublisher();
        $target = $publisher->publish($artifact, $this->temporaryRoot);
        self::assertTrue(unlink($target));
        self::assertSame(7, file_put_contents($target, 'private'));

        try {
            $publisher->publish($artifact, $this->temporaryRoot);
            self::fail('Expected publication conflict.');
        } catch (PersistenceArtifactPublicationException $failure) {
            self::assertSame('private', file_get_contents($target));
            self::assertStringNotContainsString('private', (string) $failure);
        }
    }
}
