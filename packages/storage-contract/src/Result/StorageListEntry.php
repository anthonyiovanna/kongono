<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Version\StorageObjectVersion;

/** Carries bounded list evidence without content, metadata, URL, or locator. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageListEntry
{
    public function __construct(
        private StorageObjectIdentity $identity,
        private ?StorageByteCount $byteCount = null,
        private ?StorageObjectVersion $objectVersion = null,
    ) {}

    public function identity(): StorageObjectIdentity
    {
        return $this->identity;
    }

    public function byteCount(): ?StorageByteCount
    {
        return $this->byteCount;
    }

    public function objectVersion(): ?StorageObjectVersion
    {
        return $this->objectVersion;
    }
}
