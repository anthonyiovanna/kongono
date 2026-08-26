<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\AggregateCascadePlan;
use Kongono\Persistence\AggregateLoadPlan;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Stores one detached aggregate repository definition for ordinary-PHP generation. */
#[Api(ApiStatus::Experimental)]
final readonly class AggregateRepositoryDefinition
{
    /** @var list<LoadPlanDefinition> */
    private array $loadPlans;
    /** @var list<RelationshipCascadeDefinition> */
    private array $cascades;

    /**
     * @param array<array-key, mixed> $loadPlans
     * @param array<array-key, mixed> $cascades
     */
    public function __construct(
        private string $aggregate,
        private string $className,
        private string $rootType,
        array $loadPlans,
        array $cascades,
        private int $maxChanges,
    ) {
        if (!$this->identifier($aggregate) || !$this->identifier($className) || !$this->phpName($rootType) || !array_is_list($loadPlans) || [] === $loadPlans || count($loadPlans) > AggregateLoadPlan::MAX_LOAD_PLANS_PER_REPOSITORY || !array_is_list($cascades) || count($cascades) > AggregateCascadePlan::MAX_CASCADE_POLICIES_PER_AGGREGATE || $maxChanges < 1 || $maxChanges > AggregateCascadePlan::MAX_CHANGES) {
            $this->invalid();
        }
        $acceptedPlans = [];
        $methods = [];
        foreach ($loadPlans as $plan) {
            if (!$plan instanceof LoadPlanDefinition || isset($methods[$plan->method()])) {
                $this->invalid();
            }
            $methods[$plan->method()] = true;
            $acceptedPlans[] = $plan;
        }
        $acceptedCascades = [];
        $relationships = [];
        foreach ($cascades as $cascade) {
            if (!$cascade instanceof RelationshipCascadeDefinition || isset($relationships[$cascade->relationship()])) {
                $this->invalid();
            }
            $relationships[$cascade->relationship()] = true;
            $acceptedCascades[] = $cascade;
        }
        $this->loadPlans = $acceptedPlans;
        $this->cascades = $acceptedCascades;
    }

    public function aggregate(): string
    {
        return $this->aggregate;
    }
    public function className(): string
    {
        return $this->className;
    }
    public function rootType(): string
    {
        return $this->rootType;
    }
    /** @return list<LoadPlanDefinition> */
    public function loadPlans(): array
    {
        return $this->loadPlans;
    }
    /** @return list<RelationshipCascadeDefinition> */
    public function cascades(): array
    {
        return $this->cascades;
    }
    public function maxChanges(): int
    {
        return $this->maxChanges;
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
