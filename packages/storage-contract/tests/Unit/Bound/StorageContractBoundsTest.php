<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Bound;

use Kongono\StorageContract\Bound\StorageContractBounds;
use PHPUnit\Framework\TestCase;

final class StorageContractBoundsTest extends TestCase
{
    public function testFiniteBoundsAreExact(): void
    {
        self::assertSame([
            64, 255, 64, 256, 512, 127, 32, 64, 1_024, 8_388_608, 1_099_511_627_776, 1_000,
        ], [
            StorageContractBounds::MAX_NAMESPACE_BYTES,
            StorageContractBounds::MAX_LOGICAL_IDENTITY_BYTES,
            StorageContractBounds::MAX_OPERATION_ID_BYTES,
            StorageContractBounds::MAX_VERSION_BYTES,
            StorageContractBounds::MAX_CONTINUATION_BYTES,
            StorageContractBounds::MAX_MEDIA_TYPE_BYTES,
            StorageContractBounds::MAX_METADATA_ENTRIES,
            StorageContractBounds::MAX_METADATA_NAME_BYTES,
            StorageContractBounds::MAX_METADATA_VALUE_BYTES,
            StorageContractBounds::MAX_CHUNK_BYTES,
            StorageContractBounds::MAX_OBJECT_BYTES,
            StorageContractBounds::MAX_LIST_PAGE_SIZE,
        ]);
    }
}
