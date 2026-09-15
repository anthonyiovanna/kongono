<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Exception\InvalidAssetBuildArtifactException;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Delivery\AssetDeliveryMetadata;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;

/** Carries one logical native output record and its exact operation-local bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetOutput
{
    private AssetDeliveryMetadata $deliveryMetadata;

    public function __construct(
        private LogicalAssetIdentity $identity,
        private AssetKind $kind,
        private AssetContentType $contentType,
        private ContentIdentity $contentIdentity,
        private int $byteCount,
        private AssetIntegrity $integrity,
        private PreloadHint $preloadHint,
        private AssetOutputPath $path,
        private string $bytes,
    ) {
        if ($byteCount !== strlen($bytes) || !$contentIdentity->equals(ContentIdentity::fromBytes($bytes)) || !$path->matches($contentIdentity) || IntegrityAlgorithm::Sha384 !== $integrity->algorithm() || !hash_equals(hash('sha384', $bytes), $integrity->digest()) || PreloadHint::None !== $preloadHint) {
            throw InvalidAssetBuildArtifactException::output('output_metadata_relation');
        }
        $this->deliveryMetadata = new AssetDeliveryMetadata(ContractVersion::Version1, $identity, $kind, $contentType, $contentIdentity, $byteCount, $integrity, $preloadHint);
    }

    public function identity(): LogicalAssetIdentity
    {
        return $this->identity;
    }
    public function kind(): AssetKind
    {
        return $this->kind;
    }
    public function contentType(): AssetContentType
    {
        return $this->contentType;
    }
    public function contentIdentity(): ContentIdentity
    {
        return $this->contentIdentity;
    }
    public function byteCount(): int
    {
        return $this->byteCount;
    }
    public function integrity(): AssetIntegrity
    {
        return $this->integrity;
    }
    public function preloadHint(): PreloadHint
    {
        return $this->preloadHint;
    }
    public function path(): AssetOutputPath
    {
        return $this->path;
    }
    public function bytes(): string
    {
        return $this->bytes;
    }
    public function deliveryMetadata(): AssetDeliveryMetadata
    {
        return $this->deliveryMetadata;
    }

    public function equals(self $other): bool
    {
        return $this->deliveryMetadata->equals($other->deliveryMetadata) && $this->path->equals($other->path) && hash_equals($this->bytes, $other->bytes);
    }
}
