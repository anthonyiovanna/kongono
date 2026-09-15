<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Asset;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetContract\Internal\ContractGuard;

/** Declares one logical asset using only closed neutral vocabulary. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetDeclaration
{
    public function __construct(
        private ContractVersion $contractVersion,
        private LogicalAssetIdentity $identity,
        private AssetKind $kind,
        private AssetContentType $contentType,
    ) {
        ContractGuard::kindContentType($kind, $contentType);
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

    public function equals(self $other): bool
    {
        return ContractGuard::sameContractVersion($this->contractVersion, $other->contractVersion)
            && $this->identity->equals($other->identity)
            && $this->kind === $other->kind
            && $this->contentType === $other->contentType;
    }
}
