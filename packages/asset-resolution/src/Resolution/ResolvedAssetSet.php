<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Resolution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;

/** Carries one primary resolved asset and an ordinal unique dependency list. */
#[Api(ApiStatus::Experimental)]
final readonly class ResolvedAssetSet
{
    /** @var list<ResolvedAsset> */
    private array $dependencies;

    /** @param array<array-key, mixed> $dependencies */
    public function __construct(private ResolvedAsset $primary, array $dependencies)
    {
        if (!array_is_list($dependencies)) {
            throw InvalidAssetResolutionException::relation('resolved_dependencies_not_list');
        }
        $byUrl = [];
        foreach ($dependencies as $dependency) {
            if (!$dependency instanceof ResolvedAsset || null !== $dependency->logicalIdentity()) {
                throw InvalidAssetResolutionException::relation('resolved_dependency_entry');
            }
            $url = $dependency->publicUrl()->value();
            if (isset($byUrl[$url])) {
                throw InvalidAssetResolutionException::relation('resolved_dependency_duplicate');
            }
            $byUrl[$url] = $dependency;
        }
        ksort($byUrl, SORT_STRING);
        $this->dependencies = array_values($byUrl);
    }

    public function primary(): ResolvedAsset
    {
        return $this->primary;
    }

    /** @return list<ResolvedAsset> */
    public function dependencies(): array
    {
        return [...$this->dependencies];
    }
}
