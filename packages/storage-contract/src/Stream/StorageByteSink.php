<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Stream;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Content\StorageChunk;

/**
 * Defines explicit bounded sink lifecycle without storage authority.
 *
 * Writing after close or abort is illegal. Close and abort are explicit;
 * idempotence is not promised, destructors are not lifecycle control, and
 * implementations own timely resource retirement.
 */
#[Api(ApiStatus::Experimental)]
interface StorageByteSink
{
    public function write(StorageChunk $chunk): void;

    public function close(): void;

    public function abort(): void;

    public function closed(): bool;
}
