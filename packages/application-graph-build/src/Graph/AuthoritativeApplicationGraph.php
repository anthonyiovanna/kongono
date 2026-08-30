<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\ContractVersion;

/**
 * Holds the complete canonical immutable Graph produced by the supported builder.
 *
 * @internal The constructor is a package construction seam for validated state;
 *           callers use ApplicationGraphBuilder::build().
 */
#[Api(ApiStatus::Experimental)]
final readonly class AuthoritativeApplicationGraph
{
    /** @var list<GraphNode> */
    private array $nodes;
    /** @var list<GraphRelation> */
    private array $relations;

    /** @param list<GraphNode> $nodes
     *  @param list<GraphRelation> $relations
     */
    public function __construct(private ContractVersion $version, array $nodes, array $relations)
    {
        $this->nodes = [...$nodes];
        $this->relations = [...$relations];
    }

    public function version(): ContractVersion
    {
        return $this->version;
    }
    /** @return list<GraphNode> */
    public function nodes(): array
    {
        return [...$this->nodes];
    }
    /** @return list<GraphRelation> */
    public function relations(): array
    {
        return [...$this->relations];
    }
}
