<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\AggregateCascadePlan;
use Kongono\Persistence\AggregateLoadPlan;
use Kongono\PersistenceBuild\Build\PersistenceArtifact;
use Kongono\PersistenceBuild\Build\PersistenceArtifactValidator;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Model\AggregateRepositoryDefinition;
use Kongono\PersistenceBuild\Model\LoadPlanDefinition;
use Kongono\PersistenceBuild\Model\PersistenceModel;
use Kongono\PersistenceBuild\Model\RelationshipCardinality;
use Kongono\PersistenceBuild\Model\RelationshipCascadeDefinition;
use Kongono\PersistenceBuild\Model\RelationshipDefinition;
use Kongono\PersistenceBuild\Model\RelationshipLoadDefinition;
use Kongono\PersistenceBuild\Model\RelationshipOwnership;

/** Compiles one strict separate repository source against exact-current persistence evidence. */
#[Api(ApiStatus::Internal)]
final class AggregateRepositorySourceCompiler
{
    public const SOURCE_BASENAME = 'kongono.persistence-repositories.json';
    public const MAX_REPOSITORIES = 256;

    /**
     * @return array{
     *     source_path: string,
     *     source_sha256: string,
     *     persistence_artifact: PersistenceArtifact,
     *     persistence_model_sha256: string,
     *     generated_namespace: string,
     *     repositories: list<AggregateRepositoryDefinition>
     * }
     */
    public function compile(
        string $applicationRoot,
        string $sourceRelativePath,
        string $persistenceArtifactBytes,
    ): array {
        $root = PathGuard::resolveApplicationRoot($applicationRoot);
        if (self::SOURCE_BASENAME !== basename($sourceRelativePath)) {
            $this->invalid();
        }
        $snapshot = PathGuard::readProjectFile($root, $sourceRelativePath);
        PathGuard::validateSourceText($snapshot['bytes']);
        try {
            $decoded = (new StrictJsonDecoder($snapshot['bytes'], 48))->decode();
        } catch (JsonException) {
            $this->invalid();
        }
        $source = $this->object($decoded);
        $this->assertKeys($source, ['schema', 'version', 'persistence_source', 'generated_namespace', 'repositories']);
        if ('kongono.persistence-repositories' !== ($source['schema'] ?? null) || 1 !== ($source['version'] ?? null)) {
            $this->invalid();
        }
        $persistenceSource = $source['persistence_source'] ?? null;
        $generatedNamespace = $source['generated_namespace'] ?? null;
        if (!is_string($persistenceSource) || PathGuard::SOURCE_BASENAME !== basename($persistenceSource) || !is_string($generatedNamespace) || !$this->phpName($generatedNamespace)) {
            $this->invalid();
        }
        try {
            $artifact = (new PersistenceArtifactValidator())->validateCurrent($root, $persistenceSource, $persistenceArtifactBytes);
        } catch (\Throwable $failure) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidAggregateRepositorySource, PersistenceBuildFailureCategory::AggregateRepositorySource);
        }
        $repositoryValues = $this->list($source['repositories'] ?? null);
        if ([] === $repositoryValues || count($repositoryValues) > self::MAX_REPOSITORIES) {
            $this->invalid();
        }
        $repositories = [];
        $aggregateNames = [];
        $classNames = [];
        foreach ($repositoryValues as $repositoryValue) {
            $repository = $this->object($repositoryValue);
            $this->assertKeys($repository, ['aggregate', 'class', 'load_methods', 'cascades']);
            $aggregateName = $repository['aggregate'] ?? null;
            $className = $repository['class'] ?? null;
            if (!is_string($aggregateName) || !$this->identifier($aggregateName) || !is_string($className) || !$this->identifier($className) || isset($aggregateNames[$aggregateName], $classNames[$className])) {
                $this->invalid();
            }
            $aggregate = $artifact->model()->aggregate($aggregateName);
            if (null === $aggregate) {
                $this->invalid();
            }
            $aggregateNames[$aggregateName] = true;
            $classNames[$className] = true;
            $relationships = $this->aggregateRelationships($artifact->model(), $aggregate->root());
            [$plans, $maximums] = $this->loadPlans($repository['load_methods'] ?? null, $aggregateName, $aggregate->root(), $relationships);
            [$cascades, $maxChanges] = $this->cascades($repository['cascades'] ?? null, $relationships, $maximums);
            $repositories[] = new AggregateRepositoryDefinition($aggregateName, $className, $aggregate->root(), $plans, $cascades, $maxChanges);
        }
        usort($repositories, static fn(AggregateRepositoryDefinition $left, AggregateRepositoryDefinition $right): int => strcmp($left->aggregate(), $right->aggregate()));
        PathGuard::reverify($snapshot);

        return [
            'source_path' => $snapshot['path'],
            'source_sha256' => $snapshot['sha256'],
            'persistence_artifact' => $artifact,
            'persistence_model_sha256' => $artifact->modelSha256(),
            'generated_namespace' => $generatedNamespace,
            'repositories' => $repositories,
        ];
    }

    /** @return array<string, RelationshipDefinition> */
    private function aggregateRelationships(PersistenceModel $model, string $rootType): array
    {
        $relationships = [];
        foreach ($model->relationships() as $relationship) {
            if ($relationship->source() === $rootType) {
                $relationships[$relationship->name()] = $relationship;
            }
        }

        return $relationships;
    }

    /**
     * @param array<string, RelationshipDefinition> $relationships
     * @return array{list<LoadPlanDefinition>, array<string, int>}
     */
    private function loadPlans(mixed $value, string $aggregateName, string $rootType, array $relationships): array
    {
        $values = $this->list($value);
        if ([] === $values || count($values) > AggregateLoadPlan::MAX_LOAD_PLANS_PER_REPOSITORY) {
            $this->invalid();
        }
        $plans = [];
        $methods = [];
        $maximums = [];
        foreach ($values as $planValue) {
            $plan = $this->object($planValue);
            $this->assertKeys($plan, ['method', 'relationships']);
            $method = $plan['method'] ?? null;
            if (
                !is_string($method)
                || !$this->identifier($method)
                || isset($methods[$method])
                || in_array($method, ['__construct', '__call', '__callStatic', 'aggregateName', 'rootType', 'loadPlans', 'cascadePlan', 'persist', 'remove'], true)
            ) {
                $this->invalid();
            }
            $methods[$method] = true;
            $relationshipValues = $this->list($plan['relationships'] ?? null);
            if (count($relationshipValues) > AggregateLoadPlan::MAX_RELATIONSHIPS_PER_LOAD_PLAN) {
                $this->invalid();
            }
            $loads = [];
            $names = [];
            $total = 1;
            foreach ($relationshipValues as $relationshipValue) {
                $load = $this->object($relationshipValue);
                $this->assertKeys($load, ['relationship', 'max_related']);
                $name = $load['relationship'] ?? null;
                $maxRelated = $load['max_related'] ?? null;
                if (!is_string($name) || isset($names[$name]) || !isset($relationships[$name]) || !is_int($maxRelated)) {
                    $this->invalid();
                }
                $relationship = $relationships[$name];
                if (
                    (RelationshipCardinality::ToOne === $relationship->cardinality() && 1 !== $maxRelated)
                    || (RelationshipCardinality::ToMany === $relationship->cardinality() && ($maxRelated < 1 || $maxRelated > AggregateLoadPlan::MAX_RELATED_OBJECTS_PER_TO_MANY))
                ) {
                    $this->invalid();
                }
                $order = array_map(
                    static fn($entry): array => ['field' => $entry->field(), 'direction' => $entry->direction()->value],
                    $relationship->order(),
                );
                $loads[] = new RelationshipLoadDefinition($name, $relationship->target(), $relationship->cardinality(), $relationship->nullable(), $order, $maxRelated);
                $names[$name] = true;
                $maximums[$name] = max($maximums[$name] ?? 0, $maxRelated);
                $total += $maxRelated;
            }
            if ($total > AggregateLoadPlan::MAX_TOTAL_OBJECTS) {
                $this->invalid();
            }
            $plans[] = new LoadPlanDefinition($method, $loads, $total);
        }

        return [$plans, $maximums];
    }

    /**
     * @param array<string, RelationshipDefinition> $relationships
     * @param array<string, int> $maximums
     * @return array{list<RelationshipCascadeDefinition>, int}
     */
    private function cascades(mixed $value, array $relationships, array $maximums): array
    {
        $values = $this->list($value);
        if (count($values) !== count($relationships) || count($values) > AggregateCascadePlan::MAX_CASCADE_POLICIES_PER_AGGREGATE) {
            $this->invalid();
        }
        $cascades = [];
        $names = [];
        $persistMaximum = 1;
        $removeMaximum = 1;
        foreach ($values as $cascadeValue) {
            $cascade = $this->object($cascadeValue);
            $this->assertKeys($cascade, ['relationship', 'persist', 'remove']);
            $name = $cascade['relationship'] ?? null;
            $persist = $cascade['persist'] ?? null;
            $remove = $cascade['remove'] ?? null;
            if (!is_string($name) || isset($names[$name]) || !isset($relationships[$name]) || !is_bool($persist) || !is_bool($remove)) {
                $this->invalid();
            }
            $relationship = $relationships[$name];
            if (RelationshipOwnership::Reference === $relationship->ownership() && ($persist || $remove)) {
                $this->invalid();
            }
            if (($persist || $remove) && !isset($maximums[$name])) {
                $this->invalid();
            }
            $bound = $maximums[$name] ?? 1;
            if ($persist) {
                $persistMaximum += $bound;
            }
            if ($remove) {
                $removeMaximum += $bound;
            }
            $cascades[] = new RelationshipCascadeDefinition($name, $relationship->target(), $persist, $remove, $bound);
            $names[$name] = true;
        }
        if (array_diff_key($relationships, $names) !== []) {
            $this->invalid();
        }
        $maximum = max($persistMaximum, $removeMaximum);
        if ($maximum > AggregateCascadePlan::MAX_CHANGES) {
            $this->invalid();
        }

        return [$cascades, $maximum];
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid();
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid();
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string>         $keys
     */
    private function assertKeys(array $value, array $keys): void
    {
        $actual = array_keys($value);
        sort($actual);
        sort($keys);
        if ($actual !== $keys) {
            $this->invalid();
        }
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
