<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Value;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Internal\ContractGuard;

/** Carries bounded opaque cache bytes without interpreting or serializing them. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheValueBytes
{
    private string $bytes;

    public function __construct(string $bytes)
    {
        ContractGuard::cacheValueBytes($bytes);
        $this->bytes = $bytes;
    }

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function byteCount(): int
    {
        return strlen($this->bytes);
    }

    public function contentIdentity(): CacheContentIdentity
    {
        return CacheContentIdentity::fromBytes($this->bytes);
    }

    public function equals(self $other): bool
    {
        return $this->bytes === $other->bytes;
    }
}
