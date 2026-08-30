<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;

/** Represents one admitted immutable node fact. */
#[Api(ApiStatus::Experimental)]
final readonly class GraphNode
{
    /** @var list<GraphField> */
    private array $fields;

    /** @param list<GraphField> $fields */
    public function __construct(
        private NodeIdentity $identity,
        private NodeType $type,
        array $fields,
        private GraphProvenance $provenance,
    ) {
        $this->fields = [...$fields];
    }

    public function identity(): NodeIdentity
    {
        return $this->identity;
    }
    public function type(): NodeType
    {
        return $this->type;
    }
    /** @return list<GraphField> */
    public function fields(): array
    {
        return [...$this->fields];
    }
    public function provenance(): GraphProvenance
    {
        return $this->provenance;
    }
}
