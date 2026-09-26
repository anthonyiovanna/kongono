<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Metadata;

use Kongono\StorageContract\Bound\StorageContractBounds;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Metadata\StorageMetadataEntry;
use PHPUnit\Framework\TestCase;

final class StorageMetadataTest extends TestCase
{
    public function testMetadataIsCanonicalDetachedAndComparable(): void
    {
        $entries = [new StorageMetadataEntry('zeta', 'last'), new StorageMetadataEntry('alpha', 'first')];
        $metadata = new StorageMetadata($entries);
        $entries[] = new StorageMetadataEntry('later', 'ignored');
        self::assertSame(['alpha', 'zeta'], array_map(static fn(StorageMetadataEntry $entry): string => $entry->name(), $metadata->entries()));
        self::assertSame('first', $metadata->entry('alpha')?->value());
        self::assertNull($metadata->entry('missing'));
        self::assertTrue($metadata->equals(new StorageMetadata([new StorageMetadataEntry('alpha', 'first'), new StorageMetadataEntry('zeta', 'last')])));
    }

    public function testMalformedDuplicateAndExcessMetadataFailsClosed(): void
    {
        $excess = array_map(static fn(int $index): StorageMetadataEntry => new StorageMetadataEntry('entry_' . $index, 'value'), range(0, StorageContractBounds::MAX_METADATA_ENTRIES));
        foreach ([
            static fn() => new StorageMetadata([1 => new StorageMetadataEntry('name', 'value')]),
            static fn() => new StorageMetadata(['invalid']),
            static fn() => new StorageMetadata([new StorageMetadataEntry('name', 'one'), new StorageMetadataEntry('name', 'two')]),
            static fn() => new StorageMetadata($excess),
            static fn() => new StorageMetadataEntry('Upper', 'value'),
            static fn() => new StorageMetadataEntry('name', "control\0value"),
            static fn() => new StorageMetadataEntry('name', "\xFF"),
        ] as $construct) {
            try {
                $construct();
                self::fail('Expected invalid metadata.');
            } catch (InvalidStorageContractException $failure) {
                self::assertContains($failure->diagnosticId(), ['KGN-STORAGE-CONTRACT-2103', 'KGN-STORAGE-CONTRACT-2106', 'KGN-STORAGE-CONTRACT-2108']);
            }
        }
    }
}
