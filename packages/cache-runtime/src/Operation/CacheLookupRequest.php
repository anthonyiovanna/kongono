<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Operation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Time\CacheInstant;

/** Requests one cache lookup at one caller-supplied instant. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheLookupRequest
{
    public function __construct(private LogicalCacheKey $key, private CacheInstant $now) {}

    public function key(): LogicalCacheKey
    {
        return $this->key;
    }

    public function now(): CacheInstant
    {
        return $this->now;
    }
}
