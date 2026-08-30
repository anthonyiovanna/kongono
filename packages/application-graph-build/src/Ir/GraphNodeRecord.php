<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Ir;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Exception\InvalidGraphBuildException;
use Kongono\ApplicationGraphContract\ContractVersion;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Provenance\GraphProvenance;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;

/** Carries one versioned node claim without establishing a Graph fact. */
#[Api(ApiStatus::Experimental)]
final readonly class GraphNodeRecord
{
    /** @var list<GraphFieldRecord> */
    private array $fields;

    /** @param array<array-key, mixed> $fields */
    public function __construct(
        private ContractVersion $version,
        private NodeIdentity $identity,
        private NodeType $type,
        array $fields,
        private GraphProvenance $provenance,
    ) {
        $this->fields = self::validatedFields($fields, 'node');
    }

    public function version(): ContractVersion
    {
        return $this->version;
    }
    public function identity(): NodeIdentity
    {
        return $this->identity;
    }
    public function type(): NodeType
    {
        return $this->type;
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
    private static function validatedFields(array $fields, string $recordKind): array
    {
        if (!array_is_list($fields)) {
            throw InvalidGraphBuildException::input('fields_not_list', $recordKind, count($fields));
        }
        if (count($fields) > 32) {
            throw InvalidGraphBuildException::limit('field_count', $recordKind, count($fields));
        }

        $validated = [];
        $previous = null;
        foreach ($fields as $position => $field) {
            if (!$field instanceof GraphFieldRecord) {
                throw InvalidGraphBuildException::input('invalid_field_entry', $recordKind, count($fields), $position);
            }
            $name = $field->name()->value();
            if ($name === $previous) {
                throw InvalidGraphBuildException::fieldSet('duplicate_field_name', $recordKind, count($fields), $position);
            }
            if (null !== $previous && strcmp($previous, $name) > 0) {
                throw InvalidGraphBuildException::input('non_canonical_field_order', $recordKind, count($fields), $position);
            }
            $validated[] = $field;
            $previous = $name;
        }

        return $validated;
    }
}
