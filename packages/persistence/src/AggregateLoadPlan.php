<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Exception\InvalidAggregatePersistenceException;

/** Stores one concrete named, bounded, one-level aggregate load plan. */
#[Api(ApiStatus::Experimental)]
final readonly class AggregateLoadPlan
{
    public const MAX_LOAD_PLANS_PER_REPOSITORY = 64;
    public const MAX_RELATIONSHIPS_PER_LOAD_PLAN = 64;
    public const MAX_RELATED_OBJECTS_PER_TO_MANY = 1024;
    public const MAX_TOTAL_OBJECTS = 4096;

    /** @var list<RelationshipLoad> */
    private array $relationships;

    /** @param array<array-key, mixed> $relationships */
    public function __construct(
        private string $aggregateName,
        private string $methodName,
        private string $rootType,
        array $relationships,
        private int $maxTotalObjects,
    ) {
        if (!$this->identifier($aggregateName) || !$this->identifier($methodName) || !$this->phpName($rootType) || !array_is_list($relationships) || count($relationships) > self::MAX_RELATIONSHIPS_PER_LOAD_PLAN) {
            $this->invalid();
        }
        $accepted = [];
        $names = [];
        $expectedTotal = 1;
        foreach ($relationships as $relationship) {
            if (!$relationship instanceof RelationshipLoad || isset($names[$relationship->relationshipName()])) {
                $this->invalid();
            }
            $names[$relationship->relationshipName()] = true;
            $expectedTotal += $relationship->maxRelated();
            $accepted[] = $relationship;
        }
        if ($maxTotalObjects !== $expectedTotal || $maxTotalObjects < 1 || $maxTotalObjects > self::MAX_TOTAL_OBJECTS) {
            $this->invalid();
        }
        $this->relationships = $accepted;
    }

    public function aggregateName(): string
    {
        return $this->aggregateName;
    }

    public function methodName(): string
    {
        return $this->methodName;
    }

    public function rootType(): string
    {
        return $this->rootType;
    }

    /** @return list<RelationshipLoad> */
    public function relationships(): array
    {
        return $this->relationships;
    }

    public function maxTotalObjects(): int
    {
        return $this->maxTotalObjects;
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
