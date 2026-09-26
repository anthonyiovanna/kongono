<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Version\StorageObjectVersion;

/** Carries detached object evidence without content bytes or provider location. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageObjectEvidence
{
    public function __construct(
        private StorageObjectIdentity $identity,
        private StorageByteCount $byteCount,
        private ?StorageContentDigest $contentDigest,
        private ?StorageMediaType $mediaType,
        private StorageMetadata $metadata,
        private ?StorageObjectVersion $objectVersion,
    ) {}

    public function identity(): StorageObjectIdentity
    {
        return $this->identity;
    }

    public function byteCount(): StorageByteCount
    {
        return $this->byteCount;
    }

    public function contentDigest(): ?StorageContentDigest
    {
        return $this->contentDigest;
    }

    public function mediaType(): ?StorageMediaType
    {
        return $this->mediaType;
    }

    public function metadata(): StorageMetadata
    {
        return $this->metadata;
    }

    public function objectVersion(): ?StorageObjectVersion
    {
        return $this->objectVersion;
    }
}
