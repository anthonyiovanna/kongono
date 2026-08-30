<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Exception\InvalidGraphContractException;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;

/** Defines one relation type, its canonical endpoints, and canonical fields. */
#[Api(ApiStatus::Experimental)]
final readonly class RelationSchema
{
    /** @var list<NodeType> */
    private array $allowedSources;

    /** @var list<NodeType> */
    private array $allowedTargets;

    /** @var list<FieldSchema> */
    private array $fields;

    /**
     * @param array<array-key, mixed> $allowedSources
     * @param array<array-key, mixed> $allowedTargets
     * @param array<array-key, mixed> $fields
     */
    public function __construct(
        private RelationType $type,
        array $allowedSources,
        array $allowedTargets,
        array $fields,
    ) {
        $this->allowedSources = self::validatedNodeTypes($allowedSources, 'source_type_count');
        $this->allowedTargets = self::validatedNodeTypes($allowedTargets, 'target_type_count');
        $this->fields = self::validatedFields($fields);
    }

    public function type(): RelationType
    {
        return $this->type;
    }

    /** @return list<NodeType> */
    public function allowedSources(): array
    {
        return [...$this->allowedSources];
    }

    /** @return list<NodeType> */
    public function allowedTargets(): array
    {
        return [...$this->allowedTargets];
    }

    /** @return list<FieldSchema> */
    public function fields(): array
    {
        return [...$this->fields];
    }

    /** @param array<array-key, mixed> $types
     *  @return list<NodeType>
     */
    private static function validatedNodeTypes(array $types, string $limitViolation): array
    {
        if (!array_is_list($types)) {
            throw InvalidGraphContractException::schema('node_types_not_list', count($types));
        }

        if ([] === $types) {
            throw InvalidGraphContractException::schema('required_endpoints_empty', 0);
        }

        if (count($types) > 16) {
            throw InvalidGraphContractException::limit($limitViolation, count($types));
        }

        $validated = [];
        $previous = null;

        foreach ($types as $position => $type) {
            if (!$type instanceof NodeType) {
                throw InvalidGraphContractException::schema('invalid_node_type_entry', count($types), $position);
            }

            if ($type->value === $previous) {
                throw InvalidGraphContractException::schema('duplicate_node_type', count($types), $position);
            }

            if (null !== $previous && strcmp($previous, $type->value) > 0) {
                throw InvalidGraphContractException::schema('non_canonical_node_type_order', count($types), $position);
            }

            $validated[] = $type;
            $previous = $type->value;
        }

        return $validated;
    }

    /** @param array<array-key, mixed> $fields
     *  @return list<FieldSchema>
     */
    private static function validatedFields(array $fields): array
    {
        return (new NodeSchema(NodeType::CompositionContract, $fields))->fields();
    }
}
