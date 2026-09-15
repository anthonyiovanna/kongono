<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Dependency;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Exception\InvalidAssetContractException;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetContract\Internal\ContractGuard;

/** Declares one relation without traversing or interpreting an asset graph. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetDependency
{
    public function __construct(
        private ContractVersion $contractVersion,
        private LogicalAssetIdentity $source,
        private LogicalAssetIdentity $target,
        private AssetDependencyKind $kind,
    ) {
        if ($source->equals($target)) {
            throw InvalidAssetContractException::invalidDependency('self_dependency', dependencyKind: $kind->value);
        }
    }

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }

    public function source(): LogicalAssetIdentity
    {
        return $this->source;
    }

    public function target(): LogicalAssetIdentity
    {
        return $this->target;
    }

    public function kind(): AssetDependencyKind
    {
        return $this->kind;
    }

    public function orderingKey(): string
    {
        return $this->source->value() . "\0" . $this->target->value() . "\0" . $this->kind->value;
    }

    public function equals(self $other): bool
    {
        return ContractGuard::sameContractVersion($this->contractVersion, $other->contractVersion)
            && $this->source->equals($other->source)
            && $this->target->equals($other->target)
            && $this->kind === $other->kind;
    }
}
