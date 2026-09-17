<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Request;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Version\CacheEntryVersion;

/** Requests removal only under one exact observed entry version. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheAdapterRemoveRequest
{
    public function __construct(
        private CacheKeyIdentity $keyIdentity,
        private CacheEntryVersion $expectedVersion,
    ) {}

    public function keyIdentity(): CacheKeyIdentity
    {
        return $this->keyIdentity;
    }

    public function expectedVersion(): CacheEntryVersion
    {
        return $this->expectedVersion;
    }
}
