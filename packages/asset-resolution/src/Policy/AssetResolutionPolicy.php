<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Browser\PublicAssetBaseUrl;

/** Groups the four explicit immutable application-owned resolution policies. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetResolutionPolicy
{
    public function __construct(
        private PublicAssetBaseUrl $publicBaseUrl,
        private AssetCachePolicy $cachePolicy,
        private AssetIntegrityPolicy $integrityPolicy,
        private AssetPreloadPolicy $preloadPolicy,
    ) {}

    public function publicBaseUrl(): PublicAssetBaseUrl
    {
        return $this->publicBaseUrl;
    }

    public function cachePolicy(): AssetCachePolicy
    {
        return $this->cachePolicy;
    }

    public function integrityPolicy(): AssetIntegrityPolicy
    {
        return $this->integrityPolicy;
    }

    public function preloadPolicy(): AssetPreloadPolicy
    {
        return $this->preloadPolicy;
    }
}
