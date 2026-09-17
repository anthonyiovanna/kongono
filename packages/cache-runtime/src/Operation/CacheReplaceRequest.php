<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Operation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;

/** Requests conditional replacement at one caller-supplied instant. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheReplaceRequest
{
    public function __construct(
        private LogicalCacheKey $key,
        private CacheValueBytes $value,
        private CacheEntryVersion $expectedVersion,
        private CacheInstant $now,
    ) {}

    public function key(): LogicalCacheKey
    {
        return $this->key;
    }

    public function value(): CacheValueBytes
    {
        return $this->value;
    }

    public function expectedVersion(): CacheEntryVersion
    {
        return $this->expectedVersion;
    }

    public function now(): CacheInstant
    {
        return $this->now;
    }
}
