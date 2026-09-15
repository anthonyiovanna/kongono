<?php

declare(strict_types=1);

namespace Kongono\AssetContract\EntryPoint;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetContract\Internal\ContractGuard;

/** Identifies an inert declared entry point without requesting a build or delivery. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetEntryPoint
{
    public function __construct(
        private ContractVersion $contractVersion,
        private LogicalAssetIdentity $identity,
    ) {}

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }

    public function identity(): LogicalAssetIdentity
    {
        return $this->identity;
    }

    public function equals(self $other): bool
    {
        return ContractGuard::sameContractVersion($this->contractVersion, $other->contractVersion)
            && $this->identity->equals($other->identity);
    }
}
