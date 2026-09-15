<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Browser;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Internal\ResolutionGuard;

/** Carries one public URL derived only from explicit policy and an object key. */
#[Api(ApiStatus::Experimental)]
final readonly class PublicAssetUrl
{
    private string $value;

    public function __construct(PublicAssetBaseUrl $baseUrl, string $objectKey)
    {
        ResolutionGuard::objectKeyGrammar($objectKey);
        $this->value = $baseUrl->value() . $objectKey;
        if (strlen($this->value) > 4096) {
            throw \Kongono\AssetResolution\Exception\InvalidAssetResolutionException::invalidPolicy('public_url_length');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
