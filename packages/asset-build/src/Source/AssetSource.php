<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;

/** Binds one logical asset and protected source path to exact caller-supplied bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetSource
{
    private ContentIdentity $contentIdentity;

    public function __construct(
        private LogicalAssetIdentity $identity,
        private AssetSourcePath $path,
        private string $bytes,
    ) {
        $this->contentIdentity = ContentIdentity::fromBytes($bytes);
    }

    public function identity(): LogicalAssetIdentity
    {
        return $this->identity;
    }
    public function path(): AssetSourcePath
    {
        return $this->path;
    }
    public function bytes(): string
    {
        return $this->bytes;
    }
    public function contentIdentity(): ContentIdentity
    {
        return $this->contentIdentity;
    }
    public function byteCount(): int
    {
        return strlen($this->bytes);
    }

    public function equals(self $other): bool
    {
        return $this->identity->equals($other->identity) && $this->path->equals($other->path) && hash_equals($this->bytes, $other->bytes);
    }
}
