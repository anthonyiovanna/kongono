<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Schema;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Internal\ContractGuard;
use Kongono\ViewContract\Value\ListValue;
use Kongono\ViewContract\Value\PresentationValue;
use Kongono\ViewContract\Value\RecordValue;
use Kongono\ViewContract\Value\StringValue;

/** Declares one recursive closed presentation-value schema. */
#[Api(ApiStatus::Experimental)]
final readonly class PresentationValueSchema
{
    /** @param list<PresentationFieldSchema> $fields */
    private function __construct(
        private PresentationValueKind $kind,
        private ?int $maxBytes = null,
        private ?self $itemSchema = null,
        private ?int $maxItems = null,
        private array $fields = [],
    ) {
        ContractGuard::schemaTree($this->depth(), $this->nodeCount());
    }

    public static function string(int $maxBytes = 65536): self
    {
        if ($maxBytes < 0 || $maxBytes > 65536) {
            throw InvalidViewContractException::invalidPresentationSchema('string_max_bytes_range', limit: 65536);
        }

        return new self(PresentationValueKind::String, maxBytes: $maxBytes);
    }

    public static function integer(): self
    {
        return new self(PresentationValueKind::Integer);
    }

    public static function boolean(): self
    {
        return new self(PresentationValueKind::Boolean);
    }

    public static function nullValue(): self
    {
        return new self(PresentationValueKind::Null);
    }

    public static function list(self $itemSchema, int $maxItems = 256): self
    {
        if ($maxItems < 0 || $maxItems > 256) {
            throw InvalidViewContractException::invalidPresentationSchema('list_max_items_range', limit: 256);
        }

        return new self(PresentationValueKind::List, itemSchema: $itemSchema, maxItems: $maxItems);
    }

    /** @param array<array-key, mixed> $fields */
    public static function record(array $fields): self
    {
        return new self(PresentationValueKind::Record, fields: ContractGuard::schemaFields($fields));
    }

    public function kind(): PresentationValueKind
    {
        return $this->kind;
    }

    public function maxBytes(): ?int
    {
        return $this->maxBytes;
    }

    public function itemSchema(): ?self
    {
        return $this->itemSchema;
    }

    public function maxItems(): ?int
    {
        return $this->maxItems;
    }

    /** @return list<PresentationFieldSchema> */
    public function fields(): array
    {
        return $this->fields;
    }

    public function matches(PresentationValue $value): bool
    {
        return null === $this->mismatch($value);
    }

    public function validate(PresentationValue $value): void
    {
        $mismatch = $this->mismatch($value);
        if (null !== $mismatch) {
            throw InvalidViewContractException::contextSchemaMismatch(...$mismatch);
        }
    }

    public function equals(self $other): bool
    {
        if ($this->kind !== $other->kind || $this->maxBytes !== $other->maxBytes || $this->maxItems !== $other->maxItems) {
            return false;
        }
        if ((null === $this->itemSchema) !== (null === $other->itemSchema)) {
            return false;
        }
        if (null !== $this->itemSchema && null !== $other->itemSchema && !$this->itemSchema->equals($other->itemSchema)) {
            return false;
        }
        if (count($this->fields) !== count($other->fields)) {
            return false;
        }
        foreach ($this->fields as $position => $field) {
            if (!$field->equals($other->fields[$position])) {
                return false;
            }
        }

        return true;
    }

    public function depth(): int
    {
        if (null !== $this->itemSchema) {
            return 1 + $this->itemSchema->depth();
        }

        return 1 + array_reduce($this->fields, static fn(int $depth, PresentationFieldSchema $field): int => max($depth, $field->schema()->depth()), 0);
    }

    public function nodeCount(): int
    {
        if (null !== $this->itemSchema) {
            return 1 + $this->itemSchema->nodeCount();
        }

        return 1 + array_reduce($this->fields, static fn(int $count, PresentationFieldSchema $field): int => $count + $field->schema()->nodeCount(), 0);
    }

    /** @return null|array{string, ?int, ?int, ?int, ?int, ?string, ?string} */
    private function mismatch(PresentationValue $value): ?array
    {
        if (!ContractGuard::ownedValue($value)) {
            return ['unsupported_value_implementation', null, null, null, null, null, null];
        }
        if ($value->kind() !== $this->kind) {
            return ['wrong_value_kind', null, null, null, null, $value->kind()->value, null];
        }
        if ($value instanceof StringValue && null !== $this->maxBytes && strlen($value->value()) > $this->maxBytes) {
            return ['string_bound_exceeded', null, null, $this->maxBytes, null, $value->kind()->value, null];
        }
        if ($value instanceof ListValue) {
            $values = $value->values();
            if (null === $this->maxItems || null === $this->itemSchema) {
                return ['invalid_schema_shape', null, null, null, null, $value->kind()->value, null];
            }
            if (count($values) > $this->maxItems) {
                return ['list_bound_exceeded', count($values), null, $this->maxItems, null, $value->kind()->value, null];
            }
            foreach ($values as $position => $entry) {
                if (!$this->itemSchema->matches($entry)) {
                    return ['list_item_mismatch', count($values), $position, null, null, $entry->kind()->value, null];
                }
            }
        }
        if ($value instanceof RecordValue) {
            return $this->recordMismatch($value->fields());
        }

        return null;
    }

    /**
     * @param array<string, PresentationValue> $values
     * @return null|array{string, ?int, ?int, ?int, ?int, ?string, ?string}
     */
    private function recordMismatch(array $values): ?array
    {
        $schemas = [];
        foreach ($this->fields as $position => $field) {
            $schemas[$field->name()] = [$position, $field];
            if ($field->required() && !array_key_exists($field->name(), $values)) {
                return ['required_field_missing', count($values), $position, null, null, null, null];
            }
        }
        foreach ($values as $name => $value) {
            if (!isset($schemas[$name])) {
                return ['undeclared_field', count($values), null, null, null, $value->kind()->value, null];
            }
            [$position, $field] = $schemas[$name];
            if (!$field->schema()->matches($value)) {
                return ['field_value_mismatch', count($values), $position, null, null, $value->kind()->value, null];
            }
        }

        return null;
    }
}
