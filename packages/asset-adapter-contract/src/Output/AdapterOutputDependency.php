<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Output;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;
use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
use Kongono\AssetContract\Dependency\AssetDependencyKind;

/** Relates two opaque generated outputs using the closed dependency vocabulary. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterOutputDependency
{
    public function __construct(private AdapterOutputIdentity $source, private AdapterOutputIdentity $target, private AssetDependencyKind $kind)
    {
        if ($source->equals($target)) {
            throw InvalidAssetAdapterContractException::output('self_dependency');
        }
    }
    public function source(): AdapterOutputIdentity
    {
        return $this->source;
    } public function target(): AdapterOutputIdentity
    {
        return $this->target;
    } public function kind(): AssetDependencyKind
    {
        return $this->kind;
    } public function orderingKey(): string
    {
        return $this->source->value() . "\0" . $this->kind->value . "\0" . $this->target->value();
    }
}
