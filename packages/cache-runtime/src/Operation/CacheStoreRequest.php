<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Operation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;

/** Requests absent-only storage at one caller-supplied instant. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheStoreRequest
{
    public function __construct(private LogicalCacheKey $key, private CacheValueBytes $value, private CacheInstant $now) {}

    public function key(): LogicalCacheKey
    {
        return $this->key;
    }

    public function value(): CacheValueBytes
    {
        return $this->value;
    }

    public function now(): CacheInstant
    {
        return $this->now;
    }
}
