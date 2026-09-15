<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Browser;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Internal\ResolutionGuard;

/** Carries one explicit canonical application-owned public asset base URL. */
#[Api(ApiStatus::Experimental)]
final readonly class PublicAssetBaseUrl
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ResolutionGuard::publicBaseUrl($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
