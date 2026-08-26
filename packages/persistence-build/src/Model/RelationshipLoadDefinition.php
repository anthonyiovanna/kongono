<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\AggregateLoadPlan;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Stores detached bounded relationship-load evidence for generation. */
#[Api(ApiStatus::Experimental)]
final readonly class RelationshipLoadDefinition
{
    /** @var list<array{field: string, direction: string}> */
    private array $order;

    /** @param array<array-key, mixed> $order */
    public function __construct(
        private string $relationship,
        private string $targetType,
        private RelationshipCardinality $cardinality,
        private bool $nullable,
        array $order,
        private int $maxRelated,
    ) {
        if (!$this->identifier($relationship) || !$this->phpName($targetType) || !array_is_list($order)) {
            $this->invalid();
        }
        if (
            (RelationshipCardinality::ToOne === $cardinality && 1 !== $maxRelated)
            || (RelationshipCardinality::ToMany === $cardinality && ($maxRelated < 1 || $maxRelated > AggregateLoadPlan::MAX_RELATED_OBJECTS_PER_TO_MANY))
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

    public function relationship(): string
    {
        return $this->relationship;
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
        return '' !== $value && strlen($value) <= PersistenceModelCompiler::MAX_IDENTIFIER_BYTES && 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    private function phpName(string $value): bool
    {
        return '' !== $value && strlen($value) <= PersistenceModelCompiler::MAX_FQCN_BYTES && 1 === preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    private function invalid(): never
    {
        throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidAggregateRepositorySource, PersistenceBuildFailureCategory::AggregateRepositorySource);
    }
}
