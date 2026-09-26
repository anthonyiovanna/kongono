<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Stream;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Content\StorageChunk;

/**
 * Defines explicit bounded source lifecycle without storage authority.
 *
 * The maximum is 1 through MAX_CHUNK_BYTES and null is legal only at
 * exhaustion. Reading after close is illegal. Close is explicit; idempotence
 * is not promised, destructors are not lifecycle control, and implementations
 * own timely resource retirement.
 */
#[Api(ApiStatus::Experimental)]
interface StorageByteSource
{
    public function read(int $maximumBytes): ?StorageChunk;

    public function exhausted(): bool;

    public function close(): void;

    public function closed(): bool;
}
