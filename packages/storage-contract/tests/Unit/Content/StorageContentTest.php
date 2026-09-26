<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Content;

use Kongono\StorageContract\Bound\StorageContractBounds;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageChunk;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use PHPUnit\Framework\TestCase;

final class StorageContentTest extends TestCase
{
    public function testContentEvidenceIsBoundedAndExact(): void
    {
        self::assertSame(0, (new StorageByteCount(0))->value());
        self::assertSame(StorageContractBounds::MAX_OBJECT_BYTES, (new StorageByteCount(StorageContractBounds::MAX_OBJECT_BYTES))->value());
        $chunk = new StorageChunk("\0opaque");
        self::assertSame("\0opaque", $chunk->bytes());
        self::assertSame(7, $chunk->byteCount()->value());
        self::assertTrue($chunk->equals(new StorageChunk("\0opaque")));
        self::assertSame('application/octet-stream', (new StorageMediaType('application/octet-stream'))->value());
    }

    public function testInvalidContentEvidenceFailsClosed(): void
    {
        $invalid = [
            static fn() => new StorageByteCount(-1),
            static fn() => new StorageByteCount(StorageContractBounds::MAX_OBJECT_BYTES + 1),
            static fn() => new StorageChunk(''),
            static fn() => new StorageChunk(str_repeat('x', StorageContractBounds::MAX_CHUNK_BYTES + 1)),
            static fn() => new StorageMediaType('Text/Plain'),
            static fn() => new StorageMediaType('text/plain; charset=utf-8'),
        ];
        foreach ($invalid as $construct) {
            try {
                $construct();
                self::fail('Expected invalid content evidence.');
            } catch (InvalidStorageContractException $failure) {
                self::assertContains($failure->diagnosticId(), ['KGN-STORAGE-CONTRACT-2103', 'KGN-STORAGE-CONTRACT-2108']);
            }
        }
    }
}
