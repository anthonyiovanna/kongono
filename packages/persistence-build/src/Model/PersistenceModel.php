<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Exposes only the detached normalized persistence model and explicit lookups. */
#[Api(ApiStatus::Experimental)]
final readonly class PersistenceModel
{
    /** @var list<EntityDefinition> */
    private array $entities;
    /** @var list<AggregateDefinition> */
    private array $aggregates;
    /** @var list<RelationshipDefinition> */
    private array $relationships;

    /**
     * @param array<array-key, mixed> $entities
     * @param array<array-key, mixed> $aggregates
     * @param array<array-key, mixed> $relationships
     */
    public function __construct(
        private string $generatedNamespace,
        array $entities,
        array $aggregates,
        array $relationships,
    ) {
        if (
            !$this->validPhpName($generatedNamespace)
            || !array_is_list($entities)
            || [] === $entities
            || count($entities) > PersistenceModelCompiler::MAX_ENTITIES
            || !array_is_list($aggregates)
            || [] === $aggregates
            || count($aggregates) > PersistenceModelCompiler::MAX_AGGREGATES
            || !array_is_list($relationships)
            || count($relationships) > PersistenceModelCompiler::MAX_RELATIONSHIPS
        ) {
            $this->invalid();
        }
        $acceptedEntities = [];
        $entityMap = [];
        $storageRecords = [];
        $previousEntity = null;
        $totalFields = 0;
        foreach ($entities as $entity) {
            if (!$entity instanceof EntityDefinition) {
                $this->invalid();
            }
            if (
                isset($entityMap[$entity->entity()])
                || isset($storageRecords[$entity->storageRecord()])
                || (null !== $previousEntity && strcmp($previousEntity, $entity->entity()) >= 0)
            ) {
                $this->invalid();
            }
            $entityMap[$entity->entity()] = $entity;
            $storageRecords[$entity->storageRecord()] = true;
            $previousEntity = $entity->entity();
            $totalFields += count($entity->fields());
            if ($totalFields > PersistenceModelCompiler::MAX_FIELDS) {
                $this->invalid();
            }
            $acceptedEntities[] = $entity;
        }
        $acceptedAggregates = [];
        $aggregateNames = [];
        $aggregateByEntity = [];
        $previousAggregate = null;
        $totalMembers = 0;
        foreach ($aggregates as $aggregate) {
            if (!$aggregate instanceof AggregateDefinition) {
                $this->invalid();
            }
            if (
                isset($aggregateNames[$aggregate->name()])
                || (null !== $previousAggregate && strcmp($previousAggregate, $aggregate->name()) >= 0)
            ) {
                $this->invalid();
            }
            $aggregateNames[$aggregate->name()] = true;
            $previousAggregate = $aggregate->name();
            foreach ($aggregate->members() as $member) {
                if (!isset($entityMap[$member]) || isset($aggregateByEntity[$member])) {
                    $this->invalid();
                }
                $aggregateByEntity[$member] = $aggregate->name();
                ++$totalMembers;
                if ($totalMembers > PersistenceModelCompiler::MAX_MEMBERS) {
                    $this->invalid();
                }
            }
            $acceptedAggregates[] = $aggregate;
        }
        if (count($aggregateByEntity) !== count($entityMap)) {
            $this->invalid();
        }
        $acceptedRelationships = [];
        $relationshipNames = [];
        $previousRelationship = null;
        $totalOrderEntries = 0;
        foreach ($relationships as $relationship) {
            if (!$relationship instanceof RelationshipDefinition) {
                $this->invalid();
            }
            if (
                isset($relationshipNames[$relationship->name()])
                || (null !== $previousRelationship && strcmp($previousRelationship, $relationship->name()) >= 0)
                || !isset($entityMap[$relationship->source()], $entityMap[$relationship->target()])
            ) {
                $this->invalid();
            }
            $sourceField = $entityMap[$relationship->source()]->field($relationship->sourceField());
            $targetField = $entityMap[$relationship->target()]->field($relationship->targetField());
            if (null === $sourceField || null === $targetField || $sourceField->valueKind() !== $targetField->valueKind()) {
                $this->invalid();
            }
            if (
                RelationshipOwnership::Aggregate === $relationship->ownership()
                && $aggregateByEntity[$relationship->source()] !== $aggregateByEntity[$relationship->target()]
            ) {
                $this->invalid();
            }
            foreach ($relationship->order() as $order) {
                if (null === $entityMap[$relationship->target()]->field($order->field())) {
                    $this->invalid();
                }
                ++$totalOrderEntries;
                if ($totalOrderEntries > PersistenceModelCompiler::MAX_ORDER_ENTRIES) {
                    $this->invalid();
                }
            }
            $relationshipNames[$relationship->name()] = true;
            $previousRelationship = $relationship->name();
            $acceptedRelationships[] = $relationship;
        }
        $this->entities = $acceptedEntities;
        $this->aggregates = $acceptedAggregates;
        $this->relationships = $acceptedRelationships;
    }

    public function generatedNamespace(): string
    {
        return $this->generatedNamespace;
    }
    /** @return list<EntityDefinition> */
    public function entities(): array
    {
        return $this->entities;
    }
    /** @return list<AggregateDefinition> */
    public function aggregates(): array
    {
        return $this->aggregates;
    }
    /** @return list<RelationshipDefinition> */
    public function relationships(): array
    {
        return $this->relationships;
    }

    public function entity(string $fqcn): ?EntityDefinition
    {
        foreach ($this->entities as $entity) {
            if ($entity->entity() === $fqcn) {
                return $entity;
            }
        }

        return null;
    }

    public function aggregate(string $name): ?AggregateDefinition
    {
        foreach ($this->aggregates as $aggregate) {
            if ($aggregate->name() === $name) {
                return $aggregate;
            }
        }

        return null;
    }

    public function relationship(string $name): ?RelationshipDefinition
    {
        foreach ($this->relationships as $relationship) {
            if ($relationship->name() === $name) {
                return $relationship;
            }
        }

        return null;
    }

    private function validPhpName(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= PersistenceModelCompiler::MAX_FQCN_BYTES
            && 1 === preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*)(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $value);
    }

    private function invalid(): never
    {
        throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
    }
}
