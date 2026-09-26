<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Content;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Internal\ContractGuard;

/** Carries one nonempty bounded opaque content chunk. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageChunk
{
    private string $bytes;

    public function __construct(string $bytes)
    {
        ContractGuard::chunk($bytes);
        $this->bytes = $bytes;
    }

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function byteCount(): StorageByteCount
    {
        return new StorageByteCount(strlen($this->bytes));
    }

    public function equals(self $other): bool
    {
        return $this->bytes === $other->bytes;
    }
}
