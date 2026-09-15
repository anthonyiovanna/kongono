<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Resolution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Browser\BrowserIntegrity;
use Kongono\AssetResolution\Browser\PublicAssetUrl;
use Kongono\AssetResolution\Http\AssetHttpMetadata;

/** Carries one detached presentation-neutral resolved asset value. */
#[Api(ApiStatus::Experimental)]
final readonly class ResolvedAsset
{
    public function __construct(
        private ?LogicalAssetIdentity $logicalIdentity,
        private PublicAssetUrl $publicUrl,
        private ContentIdentity $contentIdentity,
        private AssetKind $kind,
        private AssetContentType $contentType,
        private AssetHttpMetadata $httpMetadata,
        private ?BrowserIntegrity $browserIntegrity,
        private PreloadHint $preloadHint,
    ) {}

    public function logicalIdentity(): ?LogicalAssetIdentity
    {
        return $this->logicalIdentity;
    }
    public function publicUrl(): PublicAssetUrl
    {
        return $this->publicUrl;
    }
    public function contentIdentity(): ContentIdentity
    {
        return $this->contentIdentity;
    }
    public function kind(): AssetKind
    {
        return $this->kind;
    }
    public function contentType(): AssetContentType
    {
        return $this->contentType;
    }
    public function httpMetadata(): AssetHttpMetadata
    {
        return $this->httpMetadata;
    }
    public function browserIntegrity(): ?BrowserIntegrity
    {
        return $this->browserIntegrity;
    }
    public function preloadHint(): PreloadHint
    {
        return $this->preloadHint;
    }
}
