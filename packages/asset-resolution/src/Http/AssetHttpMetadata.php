<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Http;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;
use Kongono\AssetResolution\Internal\ResolutionGuard;
use Kongono\AssetResolution\Policy\AssetCachePolicy;

/** Carries inert typed HTTP metadata without emitting or mutating a response. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetHttpMetadata
{
    private string $etag;

    public function __construct(
        private AssetContentType $contentType,
        private int $contentLength,
        ContentIdentity $contentIdentity,
        private AssetCachePolicy $cachePolicy,
    ) {
        if ($contentLength < 0 || $contentLength > ResolutionGuard::MAX_CONTENT_BYTES) {
            throw InvalidAssetResolutionException::invalidHttp('content_length');
        }
        $this->etag = '"sha256-' . $contentIdentity->value() . '"';
    }

    public function contentType(): AssetContentType
    {
        return $this->contentType;
    }

    public function contentLength(): int
    {
        return $this->contentLength;
    }

    public function etag(): string
    {
        return $this->etag;
    }

    public function cacheControl(): string
    {
        return $this->cachePolicy->value;
    }

    public function cachePolicy(): AssetCachePolicy
    {
        return $this->cachePolicy;
    }
}
