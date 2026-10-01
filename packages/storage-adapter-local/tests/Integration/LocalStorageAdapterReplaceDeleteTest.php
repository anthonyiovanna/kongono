<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Integration;

use Kongono\StorageAdapterLocal\Tests\Integration\LocalStorageAdapterContainmentTest as StorageCorrectionFixture;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterReplaceDeleteTest extends TestCase
{
    public function testConditionalOutcomesRemainDistinct(): void
    {
        self::assertNotSame(StorageMutationOutcome::Conflict, StorageMutationOutcome::Absent);
        self::assertNotSame(StorageMutationOutcome::Uncertain, StorageMutationOutcome::Completed);
    }

    public function testGenerationsRejectStaleDeleteAndReplaceWithBothMediaBranches(): void
    {
        foreach (['text/plain', 'application/octet-stream'] as $media) {
            foreach (['delete', 'replace'] as $mutation) {
                $fixture = StorageCorrectionFixture::fixture();
                try {
                    [$adapter, $layout] = $fixture->adapter();
                    $first = $adapter->write(StorageCorrectionFixture::write(), StorageCorrectionFixture::source());
                    self::assertSame(StorageMutationOutcome::Completed, $first->outcome());
                    $stale = $first->resultingVersion();
                    self::assertNotNull($stale);
                    self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $stale->value());
                    self::assertSame(StorageMutationOutcome::Completed, $adapter->delete(StorageCorrectionFixture::delete($stale))->outcome());
                    $second = $adapter->write(StorageCorrectionFixture::write('recreate-one', media: $media), StorageCorrectionFixture::source());
                    $current = $second->resultingVersion();
                    self::assertNotNull($current);
                    self::assertNotSame($stale->value(), $current->value());
                    $path = $layout->objectPath('objects', 'one');
                    $before = file_get_contents($path);
                    $result = 'delete' === $mutation
                        ? $adapter->delete(StorageCorrectionFixture::delete($stale))
                        : $adapter->write(StorageCorrectionFixture::write('stale-replace', $stale), StorageCorrectionFixture::source());
                    self::assertSame(StorageMutationOutcome::Conflict, $result->outcome());
                    self::assertSame($before, file_get_contents($path));
                    $read = $adapter->read(StorageCorrectionFixture::read());
                    $evidence = $read->result()->evidence();
                    self::assertNotNull($evidence);
                    $readVersion = $evidence->objectVersion();
                    self::assertNotNull($readVersion);
                    self::assertSame($current->value(), $readVersion->value());
                    self::assertSame($media, $evidence->mediaType()?->value());
                    self::assertSame([], $evidence->metadata()->entries());
                    self::assertSame('hello', $read->source()?->read(4)?->bytes() . $read->source()?->read(4)?->bytes());
                    $read->source()?->close();
                    $positive = 'delete' === $mutation
                        ? $adapter->delete(StorageCorrectionFixture::delete($current))
                        : $adapter->write(StorageCorrectionFixture::write('current-replace', $current, $media), StorageCorrectionFixture::source());
                    self::assertSame(StorageMutationOutcome::Completed, $positive->outcome());
                    if ('replace' === $mutation) {
                        self::assertNotSame($current->value(), $positive->resultingVersion()?->value());
                    } else {
                        self::assertFileDoesNotExist($path);
                    }
                } finally {
                    $fixture->cleanup();
                }
            }
        }
    }

    public function testFixedLegacyRecordIsReadListedAndConditionallyMutatedWithoutReadRewrite(): void
    {
        $fixture = StorageCorrectionFixture::fixture();
        try {
            [$adapter, $layout] = $fixture->adapter();
            $layout->ensureNamespace('objects');
            $version = new \Kongono\StorageContract\Version\StorageObjectVersion('legacy-fixed-v1');
            $header = \Kongono\StorageAdapterLocal\Internal\LocalObjectHeader::create('objects', 'one', $layout, 5, 'sha256', hash('sha256', 'hello'), 'text/plain', [], $version->value(), $adapter->descriptor()->artifact(), $adapter->descriptor()->profileName());
            $encoded = $header->encode();
            $path = $layout->objectPath('objects', 'one');
            $record = \Kongono\StorageAdapterLocal\Internal\LocalObjectReader::MARKER . sprintf('%08x', strlen($encoded)) . "\n" . $encoded . 'hello';
            self::assertSame(strlen($record), file_put_contents($path, $record));
            $read = $adapter->read(StorageCorrectionFixture::read());
            $storedVersion = $read->result()->evidence()?->objectVersion();
            self::assertNotNull($storedVersion);
            self::assertSame('legacy-fixed-v1', $storedVersion->value());
            $read->source()?->close();
            $list = $adapter->list(StorageCorrectionFixture::list());
            $listedVersion = $list->page()?->entries()[0]->objectVersion();
            self::assertNotNull($listedVersion);
            self::assertSame('legacy-fixed-v1', $listedVersion->value());
            self::assertSame($record, file_get_contents($path));
            $replace = $adapter->write(StorageCorrectionFixture::write('replace-legacy', $version), StorageCorrectionFixture::source());
            self::assertSame(StorageMutationOutcome::Completed, $replace->outcome());
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $replace->resultingVersion()?->value() ?? '');
            self::assertNotSame($version->value(), $replace->resultingVersion()?->value());
            $current = $replace->resultingVersion();
            self::assertNotNull($current);
            self::assertSame(StorageMutationOutcome::Completed, $adapter->delete(StorageCorrectionFixture::delete($current))->outcome());
            self::assertFileDoesNotExist($path);
        } finally {
            $fixture->cleanup();
        }
    }
}
