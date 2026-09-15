<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Delivery\AssetDeliveryMetadata;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;
use Kongono\AssetResolution\Internal\ResolutionGuard;
use Throwable;

/** Carries deployment-neutral metadata for one content-addressed object. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetResolutionObject
{
    public function __construct(
        private string $objectKey,
        private AssetKind $kind,
        private AssetContentType $contentType,
        private ContentIdentity $contentIdentity,
        private int $byteCount,
        private AssetIntegrity $integrity,
        private PreloadHint $preloadHint,
    ) {
        try {
            ResolutionGuard::objectKey($objectKey, $contentIdentity);
            if (IntegrityAlgorithm::Sha384 !== $integrity->algorithm()) {
                throw InvalidAssetResolutionException::invalidArtifact('integrity_algorithm_relation');
            }
            new AssetDeliveryMetadata(ContractVersion::Version1, new LogicalAssetIdentity('resolution-object'), $kind, $contentType, $contentIdentity, $byteCount, $integrity, $preloadHint);
        } catch (Throwable $failure) {
            if ($failure instanceof InvalidAssetResolutionException) {
                throw $failure;
            }
            throw InvalidAssetResolutionException::invalidArtifact('object_metadata_relation', previous: $failure);
        }
    }

    public function objectKey(): string
    {
        return $this->objectKey;
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

    public function equals(self $other): bool
    {
        return $this->objectKey === $other->objectKey
            && $this->kind === $other->kind
            && $this->contentType === $other->contentType
            && $this->contentIdentity->equals($other->contentIdentity)
            && $this->byteCount === $other->byteCount
            && $this->integrity->equals($other->integrity)
            && $this->preloadHint === $other->preloadHint;
    }
}
