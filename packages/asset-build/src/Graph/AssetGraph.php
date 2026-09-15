<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Asset\AssetDeclarationSet;

/** Carries one detached complete validated deterministic asset graph. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetGraph
{
    /** @var list<AssetGraphNode> */
    private array $nodes;
    /** @var list<string> */
    private array $topologicalOrder;

    /**
     * @param list<AssetGraphNode> $nodes
     * @param list<string> $topologicalOrder
     */
    public function __construct(
        private AssetDeclarationSet $declarationSet,
        array $nodes,
        array $topologicalOrder,
        private int $maximumDepth,
        private AssetGraphIdentity $identity,
    ) {
        $this->nodes = [...$nodes];
        $this->topologicalOrder = [...$topologicalOrder];
    }

    public function declarationSet(): AssetDeclarationSet
    {
        return $this->declarationSet;
    }
    /** @return list<\Kongono\AssetContract\EntryPoint\AssetEntryPoint> */
    public function entryPoints(): array
    {
        return $this->declarationSet->entryPoints();
    }
    /** @return list<\Kongono\AssetContract\Dependency\AssetDependency> */
    public function dependencies(): array
    {
        return $this->declarationSet->dependencies();
    }
    /** @return list<AssetGraphNode> */
    public function nodes(): array
    {
        return [...$this->nodes];
    }
    /** @return list<string> */
    public function topologicalOrder(): array
    {
        return [...$this->topologicalOrder];
    }
    public function maximumDepth(): int
    {
        return $this->maximumDepth;
    }
    public function nodeCount(): int
    {
        return count($this->nodes);
    }
    public function dependencyCount(): int
    {
        return $this->declarationSet->dependencyCount();
    }
    public function entryPointCount(): int
    {
        return $this->declarationSet->entryPointCount();
    }
    public function identity(): AssetGraphIdentity
    {
        return $this->identity;
    }
}
