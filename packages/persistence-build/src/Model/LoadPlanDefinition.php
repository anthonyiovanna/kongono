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

/** Stores one detached concrete load method and ordered relationship evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class LoadPlanDefinition
{
    /** @var list<RelationshipLoadDefinition> */
    private array $relationships;

    /** @param array<array-key, mixed> $relationships */
    public function __construct(private string $method, array $relationships, private int $maxTotalObjects)
    {
        if ('' === $method || strlen($method) > PersistenceModelCompiler::MAX_IDENTIFIER_BYTES || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $method) || !array_is_list($relationships) || count($relationships) > AggregateLoadPlan::MAX_RELATIONSHIPS_PER_LOAD_PLAN) {
            $this->invalid();
        }
        $accepted = [];
        $names = [];
        $total = 1;
        foreach ($relationships as $relationship) {
            if (!$relationship instanceof RelationshipLoadDefinition || isset($names[$relationship->relationship()])) {
                $this->invalid();
            }
            $names[$relationship->relationship()] = true;
            $total += $relationship->maxRelated();
            $accepted[] = $relationship;
        }
        if ($maxTotalObjects !== $total || $maxTotalObjects > AggregateLoadPlan::MAX_TOTAL_OBJECTS) {
            $this->invalid();
        }
        $this->relationships = $accepted;
    }

    public function method(): string
    {
        return $this->method;
    }
    /** @return list<RelationshipLoadDefinition> */
    public function relationships(): array
    {
        return $this->relationships;
    }
    public function maxTotalObjects(): int
    {
        return $this->maxTotalObjects;
    }

    private function invalid(): never
    {
        throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidAggregateRepositorySource, PersistenceBuildFailureCategory::AggregateRepositorySource);
    }
}
