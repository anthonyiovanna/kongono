<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Output;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;
use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
use Kongono\AssetBuild\Build\AssetOutputPath;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Identity\ContentIdentity;

/** Carries one generated adapter output and exact operation-local bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class AdaptedAssetOutput
{
    public function __construct(private AdapterOutputIdentity $identity, private AssetKind $kind, private AssetContentType $contentType, private ContentIdentity $contentIdentity, private int $byteCount, private AssetIntegrity $integrity, private PreloadHint $preloadHint, private AssetOutputPath $path, private string $bytes)
    {
        try {
            new \Kongono\AssetContract\Delivery\AssetDeliveryMetadata(\Kongono\AssetContract\ContractVersion::Version1, new \Kongono\AssetContract\Identity\LogicalAssetIdentity('adapter-output'), $kind, $contentType, $contentIdentity, $byteCount, $integrity, $preloadHint);
        } catch (\Throwable $failure) {
            throw InvalidAssetAdapterContractException::output('output_metadata_relation', previous: $failure);
        }
        if ($byteCount !== strlen($bytes) || !$contentIdentity->equals(ContentIdentity::fromBytes($bytes)) || !$path->matches($contentIdentity) || IntegrityAlgorithm::Sha384 !== $integrity->algorithm() || !hash_equals(hash('sha384', $bytes), $integrity->digest()) || PreloadHint::None !== $preloadHint) {
            throw InvalidAssetAdapterContractException::output('output_metadata_relation');
        }
    }
    public function identity(): AdapterOutputIdentity
    {
        return $this->identity;
    } public function kind(): AssetKind
    {
        return $this->kind;
    } public function contentType(): AssetContentType
    {
        return $this->contentType;
    } public function contentIdentity(): ContentIdentity
    {
        return $this->contentIdentity;
    } public function byteCount(): int
    {
        return $this->byteCount;
    } public function integrity(): AssetIntegrity
    {
        return $this->integrity;
    } public function preloadHint(): PreloadHint
    {
        return $this->preloadHint;
    } public function path(): AssetOutputPath
    {
        return $this->path;
    } public function bytes(): string
    {
        return $this->bytes;
    }
    public function equals(self $other): bool
    {
        return $this->identity->equals($other->identity) && $this->kind === $other->kind && $this->contentType === $other->contentType && $this->contentIdentity->equals($other->contentIdentity) && $this->path->equals($other->path) && hash_equals($this->bytes, $other->bytes);
    }
}
