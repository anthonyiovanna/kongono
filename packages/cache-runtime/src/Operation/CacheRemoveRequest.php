<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Operation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Version\CacheEntryVersion;

/** Requests conditional removal at one caller-supplied instant. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheRemoveRequest
{
    public function __construct(private LogicalCacheKey $key, private CacheEntryVersion $expectedVersion, private CacheInstant $now) {}

    public function key(): LogicalCacheKey
    {
        return $this->key;
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
