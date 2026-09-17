<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Request;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Identity\CacheKeyIdentity;

/** Requests lookup for one exact bounded key identity. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheAdapterLookupRequest
{
    public function __construct(private CacheKeyIdentity $keyIdentity) {}

    public function keyIdentity(): CacheKeyIdentity
    {
        return $this->keyIdentity;
    }
}
