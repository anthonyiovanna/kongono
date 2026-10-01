<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Integration;

use Kongono\StorageAdapterLocal\Tests\Integration\LocalStorageAdapterContainmentTest as StorageCorrectionFixture;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Stream\StorageByteSource;
use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterStreamingTest extends TestCase
{
    public function testSourceContractRequiresExplicitLifecycle(): void
    {
        $methods = array_map(static fn(\ReflectionMethod $m): string => $m->getName(), (new \ReflectionClass(StorageByteSource::class))->getMethods());
        self::assertSame(['read','exhausted','close','closed'], $methods);
    }

    public function testCollisionAndEveryInvalidSourceRetireOnlyOwnedTemporary(): void
    {
        $fixture = StorageCorrectionFixture::fixture();
        try {
            [$adapter, $layout] = $fixture->adapter();
            $layout->ensureNamespace('objects');
            $temporary = $layout->temporaryPath('objects', 'one', 'create-one');
            self::assertSame(8, file_put_contents($temporary, 'sentinel'));
            $before = hash_file('sha256', $temporary);
            $failedExclusive = @fopen($temporary, 'x+b');
            self::assertFalse($failedExclusive);
            $collisionSource = StorageCorrectionFixture::source();
            self::assertSame(StorageMutationOutcome::Unavailable, $adapter->write(StorageCorrectionFixture::write(), $collisionSource)->outcome());
            self::assertSame($before, hash_file('sha256', $temporary));
            self::assertSame('sentinel', file_get_contents($temporary));
            self::assertFalse($collisionSource->closed());
            self::assertFileDoesNotExist($layout->objectPath('objects', 'one'));
            self::assertTrue(unlink($temporary));
            self::assertTrue(mkdir($temporary));
            self::assertSame(StorageMutationOutcome::Unavailable, $adapter->write(StorageCorrectionFixture::write(), StorageCorrectionFixture::source())->outcome());
            self::assertDirectoryExists($temporary);
            self::assertTrue(rmdir($temporary));
            foreach (['hell', 'helloo', 'other', 'throw'] as $invalid) {
                $source = StorageCorrectionFixture::source('throw' === $invalid ? 'hello' : $invalid, 'throw' === $invalid ? static function (): void {
                    throw new \RuntimeException('synthetic-source-failure');
                } : null);
                self::assertSame(StorageMutationOutcome::Rejected, $adapter->write(StorageCorrectionFixture::write(), $source)->outcome());
                self::assertFalse($source->closed());
                self::assertFileDoesNotExist($temporary);
                self::assertFileDoesNotExist($layout->objectPath('objects', 'one'));
                $control = $adapter->write(StorageCorrectionFixture::write('control-' . $invalid), StorageCorrectionFixture::source());
                self::assertSame(StorageMutationOutcome::Completed, $control->outcome());
                $version = $control->resultingVersion();
                self::assertNotNull($version);
                self::assertSame(StorageMutationOutcome::Completed, $adapter->delete(StorageCorrectionFixture::delete($version))->outcome());
            }
        } finally {
            $fixture->cleanup();
        }
    }

    public function testOwnedHandleRetirementFailureIsUnavailableAndStillRemovesOwnedTemporary(): void
    {
        $fixture = StorageCorrectionFixture::fixture();
        try {
            [$adapter, $layout] = $fixture->adapter();
            $temporary = $layout->temporaryPath('objects', 'one', 'create-one');
            $retired = 0;
            $source = StorageCorrectionFixture::source(before: static function () use ($temporary, &$retired): void {
                foreach (get_resources('stream') as $handle) {
                    $metadata = stream_get_meta_data($handle);
                    $uri = $metadata['uri'] ?? null;
                    if (is_string($uri) && \Kongono\StorageAdapterLocal\Internal\LocalAdapterGuard::normalize($uri) === $temporary) {
                        self::assertTrue(fclose($handle));
                        ++$retired;
                    }
                }
                self::assertSame(1, $retired);
                throw new \RuntimeException('synthetic-owned-handle-retired');
            });
            self::assertSame(StorageMutationOutcome::Unavailable, $adapter->write(StorageCorrectionFixture::write(), $source)->outcome());
            self::assertSame(1, $retired);
            self::assertFalse($source->closed());
            self::assertFileDoesNotExist($temporary);
            self::assertFileDoesNotExist($layout->objectPath('objects', 'one'));
            $control = $adapter->write(StorageCorrectionFixture::write('after-cleanup-failure'), StorageCorrectionFixture::source());
            self::assertSame(StorageMutationOutcome::Completed, $control->outcome());
            self::assertFileExists($layout->objectPath('objects', 'one'));
            $source->close();
        } finally {
            $fixture->cleanup();
        }
    }
}
