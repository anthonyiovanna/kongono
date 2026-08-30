<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Ir;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Exception\InvalidGraphBuildException;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Identity\RelationIdentity;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;

/** Carries one versioned relation claim without proving endpoints or a fact. */
#[Api(ApiStatus::Experimental)]
final readonly class GraphRelationRecord
{
    /** @var list<GraphFieldRecord> */
    private array $fields;

    /** @param array<array-key, mixed> $fields */
    public function __construct(
        private ContractVersion $version,
        private RelationIdentity $identity,
        private RelationType $type,
        private NodeIdentity $source,
        private NodeIdentity $target,
        array $fields,
        private GraphProvenance $provenance,
    ) {
        $this->fields = self::validatedFields($fields);
    }

    public function version(): ContractVersion
    {
        return $this->version;
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
    /** @return list<GraphFieldRecord> */
    public function fields(): array
    {
        return [...$this->fields];
    }
    public function provenance(): GraphProvenance
    {
        return $this->provenance;
    }

    /** @param array<array-key, mixed> $fields
     *  @return list<GraphFieldRecord>
     */
    private static function validatedFields(array $fields): array
    {
        if (!array_is_list($fields)) {
            throw InvalidGraphBuildException::input('fields_not_list', 'relation', count($fields));
        }
        if (count($fields) > 32) {
            throw InvalidGraphBuildException::limit('field_count', 'relation', count($fields));
        }

        $validated = [];
        $previous = null;
        foreach ($fields as $position => $field) {
            if (!$field instanceof GraphFieldRecord) {
                throw InvalidGraphBuildException::input('invalid_field_entry', 'relation', count($fields), $position);
            }
            $name = $field->name()->value();
            if ($name === $previous) {
                throw InvalidGraphBuildException::fieldSet('duplicate_field_name', 'relation', count($fields), $position);
            }
            if (null !== $previous && strcmp($previous, $name) > 0) {
                throw InvalidGraphBuildException::input('non_canonical_field_order', 'relation', count($fields), $position);
            }
            $validated[] = $field;
            $previous = $name;
        }

        return $validated;
    }
}
