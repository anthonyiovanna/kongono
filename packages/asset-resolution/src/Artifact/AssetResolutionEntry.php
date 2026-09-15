<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;

/** Binds one logical identity to a primary object and complete dependency closure. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetResolutionEntry
{
    /** @var list<array{kind: AssetDependencyKind, object: AssetResolutionObject}> */
    private array $dependencies;

    /** @param array<array-key, mixed> $dependencies */
    public function __construct(
        private LogicalAssetIdentity $identity,
        private AssetResolutionObject $primary,
        array $dependencies,
    ) {
        if (!array_is_list($dependencies) || count($dependencies) > 2047) {
            throw InvalidAssetResolutionException::invalidArtifact('dependency_object_list');
        }
        $byKey = [];
        foreach ($dependencies as $dependency) {
            if (!is_array($dependency) || array_keys($dependency) !== ['kind', 'object'] || !$dependency['kind'] instanceof AssetDependencyKind || !$dependency['object'] instanceof AssetResolutionObject) {
                throw InvalidAssetResolutionException::invalidArtifact('dependency_object_entry');
            }
            $key = $dependency['object']->objectKey();
            if ($primary->objectKey() === $key || isset($byKey[$key])) {
                throw InvalidAssetResolutionException::invalidArtifact('dependency_object_uniqueness');
            }
            $byKey[$key] = ['kind' => $dependency['kind'], 'object' => $dependency['object']];
        }
        ksort($byKey, SORT_STRING);
        $this->dependencies = array_values($byKey);
    }

    public function identity(): LogicalAssetIdentity
    {
        return $this->identity;
    }

    public function primary(): AssetResolutionObject
    {
        return $this->primary;
    }

    /** @return list<array{kind: AssetDependencyKind, object: AssetResolutionObject}> */
    public function dependencies(): array
    {
        return [...$this->dependencies];
    }

    public function dependencyCount(): int
    {
        return count($this->dependencies);
    }
}
