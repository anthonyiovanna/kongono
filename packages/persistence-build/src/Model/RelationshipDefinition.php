<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Stores one validated declarative relationship without runtime semantics. */
#[Api(ApiStatus::Experimental)]
final readonly class RelationshipDefinition
{
    /** @var list<RelationshipOrder> */
    private array $order;

    /** @param array<array-key, mixed> $order */
    public function __construct(
        private string $name,
        private string $source,
        private string $target,
        private string $sourceField,
        private string $targetField,
        private RelationshipCardinality $cardinality,
        private bool $nullable,
        private RelationshipOwnership $ownership,
        array $order,
    ) {
        if (
            !$this->validIdentifier($name)
            || !$this->validPhpName($source)
            || !$this->validPhpName($target)
            || !$this->validIdentifier($sourceField)
            || !$this->validIdentifier($targetField)
            || !array_is_list($order)
            || count($order) > PersistenceModelCompiler::MAX_ORDER_ENTRIES
        ) {
            throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $accepted = [];
        $seen = [];
        foreach ($order as $entry) {
            if (!$entry instanceof RelationshipOrder || isset($seen[$entry->field()])) {
                throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
            }
            $seen[$entry->field()] = true;
            $accepted[] = $entry;
        }
        if ((RelationshipCardinality::ToOne === $cardinality) === ([] !== $accepted)) {
            throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $this->order = $accepted;
    }

    public function name(): string
    {
        return $this->name;
    }
    public function source(): string
    {
        return $this->source;
    }
    public function target(): string
    {
        return $this->target;
    }
    public function sourceField(): string
    {
        return $this->sourceField;
    }
    public function targetField(): string
    {
        return $this->targetField;
    }
    public function cardinality(): RelationshipCardinality
    {
        return $this->cardinality;
    }
    public function nullable(): bool
    {
        return $this->nullable;
    }
    public function ownership(): RelationshipOwnership
    {
        return $this->ownership;
    }
    /** @return list<RelationshipOrder> */
    public function order(): array
    {
        return $this->order;
    }

    private function validIdentifier(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= PersistenceModelCompiler::MAX_IDENTIFIER_BYTES
            && 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    private function validPhpName(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= PersistenceModelCompiler::MAX_FQCN_BYTES
            && 1 === preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*)(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $value);
    }
}
