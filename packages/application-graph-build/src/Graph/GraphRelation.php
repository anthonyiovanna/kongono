<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Identity\RelationIdentity;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;

/** Represents one admitted immutable relation fact. */
#[Api(ApiStatus::Experimental)]
final readonly class GraphRelation
{
    /** @var list<GraphField> */
    private array $fields;

    /** @param list<GraphField> $fields */
    public function __construct(
        private RelationIdentity $identity,
        private RelationType $type,
        private NodeIdentity $source,
        private NodeIdentity $target,
        array $fields,
        private GraphProvenance $provenance,
    ) {
        $this->fields = [...$fields];
    }

    public function identity(): RelationIdentity
    {
        return $this->identity;
    }
    public function type(): RelationType
    {
        return $this->type;
    }
    public function source(): NodeIdentity
    {
        return $this->source;
    }
    public function target(): NodeIdentity
    {
        return $this->target;
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
