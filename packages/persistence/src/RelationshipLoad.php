<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Exception\InvalidAggregatePersistenceException;

/** Stores one bounded relationship request in a named aggregate load plan. */
#[Api(ApiStatus::Experimental)]
final readonly class RelationshipLoad
{
    /** @var list<array{field: string, direction: string}> */
    private array $order;

    /** @param array<array-key, mixed> $order */
    public function __construct(
        private string $relationshipName,
        private string $targetType,
        private RelationshipCardinality $cardinality,
        private bool $nullable,
        array $order,
        private int $maxRelated,
    ) {
        if (!$this->identifier($relationshipName) || !$this->phpName($targetType)) {
            $this->invalid();
        }
        if (
            (RelationshipCardinality::ToOne === $cardinality && 1 !== $maxRelated)
            || (RelationshipCardinality::ToMany === $cardinality && ($maxRelated < 1 || $maxRelated > AggregateLoadPlan::MAX_RELATED_OBJECTS_PER_TO_MANY))
            || !array_is_list($order)
            || count($order) > AggregateLoadPlan::MAX_RELATIONSHIPS_PER_LOAD_PLAN
        ) {
            $this->invalid();
        }
        $accepted = [];
        $fields = [];
        foreach ($order as $entry) {
            if (!is_array($entry) || array_keys($entry) !== ['field', 'direction']) {
                $this->invalid();
            }
            $field = $entry['field'] ?? null;
            $direction = $entry['direction'] ?? null;
            if (!is_string($field) || !$this->identifier($field) || !is_string($direction) || !in_array($direction, ['ascending', 'descending'], true) || isset($fields[$field])) {
                $this->invalid();
            }
            $fields[$field] = true;
            $accepted[] = ['field' => $field, 'direction' => $direction];
        }
        if ((RelationshipCardinality::ToMany === $cardinality) !== ([] !== $accepted)) {
            $this->invalid();
        }
        $this->order = $accepted;
    }

    public function relationshipName(): string
    {
        return $this->relationshipName;
    }

    public function targetType(): string
    {
        return $this->targetType;
    }

    public function cardinality(): RelationshipCardinality
    {
        return $this->cardinality;
    }

    public function nullable(): bool
    {
        return $this->nullable;
    }

    /** @return list<array{field: string, direction: string}> */
    public function order(): array
    {
        return $this->order;
    }

    public function maxRelated(): int
    {
        return $this->maxRelated;
    }

    private function identifier(string $value): bool
    {
        return '' !== $value && strlen($value) <= 191 && 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    private function phpName(string $value): bool
    {
        return '' !== $value && strlen($value) <= 255 && 1 === preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    private function invalid(): never
    {
        throw InvalidAggregatePersistenceException::invalid();
    }
}
