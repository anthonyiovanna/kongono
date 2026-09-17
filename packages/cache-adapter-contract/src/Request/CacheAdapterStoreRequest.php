<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Request;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;

/** Requests absent-only storage of one complete entry candidate. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheAdapterStoreRequest
{
    public function __construct(private CacheAdapterEntry $entry) {}

    public function entry(): CacheAdapterEntry
    {
        return $this->entry;
    }
}
