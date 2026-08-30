<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Exception\InvalidGraphContractException;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;

/** Defines one node type and its caller-supplied canonical field list. */
#[Api(ApiStatus::Experimental)]
final readonly class NodeSchema
{
    /** @var list<FieldSchema> */
    private array $fields;

    /** @param array<array-key, mixed> $fields */
    public function __construct(private NodeType $type, array $fields)
    {
        $this->fields = self::validatedFields($fields);
    }

    public function type(): NodeType
    {
        return $this->type;
    }

    /** @return list<FieldSchema> */
    public function fields(): array
    {
        return [...$this->fields];
    }

    /** @param array<array-key, mixed> $fields
     *  @return list<FieldSchema>
     */
    private static function validatedFields(array $fields): array
    {
        if (!array_is_list($fields)) {
            throw InvalidGraphContractException::schema('fields_not_list', count($fields));
        }

        if (count($fields) > 32) {
            throw InvalidGraphContractException::limit('field_count', count($fields));
        }

        $validated = [];
        $previous = null;

        foreach ($fields as $position => $field) {
            if (!$field instanceof FieldSchema) {
                throw InvalidGraphContractException::schema('invalid_field_entry', count($fields), $position);
            }

            $name = $field->name()->value();

            if ($name === $previous) {
                throw InvalidGraphContractException::schema('duplicate_field_name', count($fields), $position);
            }

            if (null !== $previous && strcmp($previous, $name) > 0) {
                throw InvalidGraphContractException::schema('non_canonical_field_order', count($fields), $position);
            }

            $validated[] = $field;
            $previous = $name;
        }

        return $validated;
    }
}
