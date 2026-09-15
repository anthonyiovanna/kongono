<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Browser;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;

/** Carries exact browser SRI text derived from validated SHA-384 evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class BrowserIntegrity
{
    private string $value;

    public function __construct(AssetIntegrity $integrity)
    {
        if (IntegrityAlgorithm::Sha384 !== $integrity->algorithm()) {
            throw InvalidAssetResolutionException::integrityPolicy('sha384_required');
        }
        $bytes = hex2bin($integrity->digest());
        if (false === $bytes || 48 !== strlen($bytes)) {
            throw InvalidAssetResolutionException::integrityPolicy('sha384_evidence');
        }
        $this->value = 'sha384-' . base64_encode($bytes);
    }

    public function value(): string
    {
        return $this->value;
    }
}
