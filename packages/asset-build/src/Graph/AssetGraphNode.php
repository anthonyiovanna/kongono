<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Dependency\AssetDependency;

/** Carries one detached canonical graph node and its outgoing dependency edges. */
#[Api(ApiStatus::Internal)]
final readonly class AssetGraphNode
{
    /** @var list<AssetDependency> */
    private array $dependencies;

    /** @param list<AssetDependency> $dependencies */
    public function __construct(private AssetDeclaration $declaration, array $dependencies)
    {
        $this->dependencies = [...$dependencies];
    }

    public function declaration(): AssetDeclaration
    {
        return $this->declaration;
    }
    /** @return list<AssetDependency> */
    public function dependencies(): array
    {
        return [...$this->dependencies];
    }
    public function dependencyCount(): int
    {
        return count($this->dependencies);
    }
}
