<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Generated;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Exception\GeneratedPersistenceStatePublicationException;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStatePublisher;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

final class GeneratedPersistenceStatePublisherTest extends TestCase
{
    private string $temporaryRoot;

    protected function setUp(): void
    {
        $this->temporaryRoot = sys_get_temp_dir() . '/kongono-persistence-state-' . bin2hex(random_bytes(8));
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

    public function testInitialPublicationAndIdenticalRepublishAreAtomicAndIdempotent(): void
    {
        $artifact = $this->generated();
        $publisher = new GeneratedPersistenceStatePublisher();
        $first = $publisher->publish($artifact, $this->temporaryRoot);
        $second = $publisher->publish($artifact, $this->temporaryRoot);

        self::assertSame($first, $second);
        self::assertSame(
            'kongono-persistence-state-v1-' . $artifact->generatedFileSha256() . '.php',
            basename($first),
        );
        self::assertSame($artifact->canonicalBytes(), file_get_contents($first));
        self::assertSame([], glob($this->temporaryRoot . '/.kongono-persistence-state-*'));
    }

    public function testDifferentExistingBytesAndUnsafeOutputTypesAreNeverClobbered(): void
    {
        $artifact = $this->generated();
        $publisher = new GeneratedPersistenceStatePublisher();
        $target = $publisher->publish($artifact, $this->temporaryRoot);
        self::assertTrue(unlink($target));
        self::assertSame(7, file_put_contents($target, 'private'));

        try {
            $publisher->publish($artifact, $this->temporaryRoot);
            self::fail('Expected no-clobber publication failure.');
        } catch (GeneratedPersistenceStatePublicationException $failure) {
            self::assertSame('private', file_get_contents($target));
            self::assertStringNotContainsString('private', (string) $failure);
        }

        $notDirectory = $this->temporaryRoot . '/not-directory';
        self::assertSame(7, file_put_contents($notDirectory, 'private'));
        $this->expectException(GeneratedPersistenceStatePublicationException::class);
        $publisher->publish($artifact, $notDirectory);
    }

    private function generated(): \Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateArtifact
    {
        $root = dirname(__DIR__, 5);
        $source = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
        $persistence = (new PersistenceArtifactBuilder())->build($root, $source);

        return (new GeneratedPersistenceStateGenerator())->generate($root, $source, $persistence->canonicalBytes());
    }
}
