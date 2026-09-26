<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Exception\InvalidStorageProfileException;
use Kongono\StorageContract\Bound\StorageContractBounds;

/** Carries explicit profile bounds without claiming provider guarantees. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageProfileLimits
{
    public function __construct(
        private int $maxChunkBytes,
        private int $maxObjectBytes,
        private int $maxListPageSize,
    ) {
        if ($maxChunkBytes < 1 || $maxChunkBytes > StorageContractBounds::MAX_CHUNK_BYTES) {
            throw InvalidStorageProfileException::invalid('max_chunk_bytes', limit: StorageContractBounds::MAX_CHUNK_BYTES);
        }
        if ($maxObjectBytes < 1 || $maxObjectBytes > StorageContractBounds::MAX_OBJECT_BYTES) {
            throw InvalidStorageProfileException::invalid('max_object_bytes', limit: StorageContractBounds::MAX_OBJECT_BYTES);
        }
        if ($maxListPageSize < 1 || $maxListPageSize > StorageContractBounds::MAX_LIST_PAGE_SIZE) {
            throw InvalidStorageProfileException::invalid('max_list_page_size', limit: StorageContractBounds::MAX_LIST_PAGE_SIZE);
        }
        if ($maxChunkBytes > $maxObjectBytes) {
            throw InvalidStorageProfileException::invalid('chunk_object_relation');
        }
    }

    public function maxChunkBytes(): int
    {
        return $this->maxChunkBytes;
    }

    public function maxObjectBytes(): int
    {
        return $this->maxObjectBytes;
    }

    public function maxListPageSize(): int
    {
        return $this->maxListPageSize;
    }

    public function equals(self $other): bool
    {
        return $this->maxChunkBytes === $other->maxChunkBytes
            && $this->maxObjectBytes === $other->maxObjectBytes
            && $this->maxListPageSize === $other->maxListPageSize;
    }
}
