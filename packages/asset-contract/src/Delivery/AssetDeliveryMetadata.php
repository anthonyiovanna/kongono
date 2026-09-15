<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Delivery;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Exception\InvalidAssetContractException;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetContract\Internal\ContractGuard;

/** Carries bounded inert delivery evidence without paths, URLs, or HTTP policy. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetDeliveryMetadata
{
    public function __construct(
        private ContractVersion $contractVersion,
        private LogicalAssetIdentity $identity,
        private AssetKind $kind,
        private AssetContentType $contentType,
        private ContentIdentity $contentIdentity,
        private int $byteCount,
        private ?AssetIntegrity $integrity,
        private PreloadHint $preloadHint,
    ) {
        ContractGuard::kindContentType($kind, $contentType, true);
        if ($byteCount < 0 || $byteCount > ContractGuard::MAX_CONTENT_BYTES) {
            throw InvalidAssetContractException::invalidDeliveryMetadata('byte_count_range');
        }
        if (PreloadHint::ModulePreload === $preloadHint && AssetKind::Script !== $kind) {
            throw InvalidAssetContractException::invalidDeliveryMetadata('module_preload_kind_relation', assetKind: $kind->value, preloadHint: $preloadHint->value);
        }
        if (null !== $integrity && IntegrityAlgorithm::Sha256 === $integrity->algorithm() && $integrity->digest() !== $contentIdentity->value()) {
            throw InvalidAssetContractException::invalidDeliveryMetadata('integrity_content_identity_relation', algorithm: $integrity->algorithm()->value);
        }
    }

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
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

    public function integrity(): ?AssetIntegrity
    {
        return $this->integrity;
    }

    public function preloadHint(): PreloadHint
    {
        return $this->preloadHint;
    }

    public function equals(self $other): bool
    {
        return ContractGuard::sameContractVersion($this->contractVersion, $other->contractVersion)
            && $this->identity->equals($other->identity)
            && $this->kind === $other->kind
            && $this->contentType === $other->contentType
            && $this->contentIdentity->equals($other->contentIdentity)
            && $this->byteCount === $other->byteCount
            && (
                (null === $this->integrity && null === $other->integrity)
                || (null !== $this->integrity && null !== $other->integrity && $this->integrity->equals($other->integrity))
            )
            && $this->preloadHint === $other->preloadHint;
    }
}
