<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Request;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheContract\Version\CacheEntryVersion;

/** Requests replacement only under one exact observed entry version. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheAdapterReplaceRequest
{
    public function __construct(
        private CacheAdapterEntry $entry,
        private CacheEntryVersion $expectedVersion,
    ) {}

    public function entry(): CacheAdapterEntry
    {
        return $this->entry;
    }

    public function expectedVersion(): CacheEntryVersion
    {
        return $this->expectedVersion;
    }
}
