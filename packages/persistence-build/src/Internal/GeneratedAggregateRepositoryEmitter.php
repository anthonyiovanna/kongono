<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Model\AggregateRepositoryDefinition;
use Kongono\PersistenceBuild\Model\LoadPlanDefinition;
use Kongono\PersistenceBuild\Model\RelationshipLoadDefinition;

/** Emits deterministic stateless aggregate repositories as ordinary PHP. */
#[Api(ApiStatus::Internal)]
final class GeneratedAggregateRepositoryEmitter
{
    /** @param list<AggregateRepositoryDefinition> $repositories */
    public function emit(
        string $sourcePath,
        string $sourceSha256,
        string $persistenceArtifactSha256,
        string $persistenceModelSha256,
        string $persistenceStateGeneratorSha256,
        string $generatorSha256,
        string $generatedNamespace,
        array $repositories,
    ): string {
        $classMap = [];
        foreach ($repositories as $repository) {
            $classMap[] = [
                'aggregate' => $repository->aggregate(),
                'repository' => $generatedNamespace . '\\' . $repository->className(),
                'root_type' => $repository->rootType(),
                'load_methods' => array_map(static fn(LoadPlanDefinition $plan): string => $plan->method(), $repository->loadPlans()),
            ];
        }
        $metadata = json_encode([
            'schema' => 'kongono.generated-aggregate-repositories',
            'version' => 1,
            'aggregate_repository_source_path' => $sourcePath,
            'aggregate_repository_source_sha256' => $sourceSha256,
            'persistence_artifact_sha256' => $persistenceArtifactSha256,
            'persistence_model_sha256' => $persistenceModelSha256,
            'persistence_state_generator_implementation_sha256' => $persistenceStateGeneratorSha256,
            'aggregate_repository_generator_implementation_sha256' => $generatorSha256,
            'generated_namespace' => $generatedNamespace,
            'class_map' => $classMap,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '// ' . $metadata,
            '',
            'namespace ' . $generatedNamespace . ';',
            '',
            'use Kongono\\Database\\DatabaseExecutor;',
            'use Kongono\\Persistence\\AggregateCascadeExecutor;',
            'use Kongono\\Persistence\\AggregateCascadePlan;',
            'use Kongono\\Persistence\\AggregateLoadExecutor;',
            'use Kongono\\Persistence\\AggregateLoadPlan;',
            'use Kongono\\Persistence\\AggregateRepository;',
            'use Kongono\\Persistence\\CascadeAction;',
            'use Kongono\\Persistence\\EntityIdentity;',
            'use Kongono\\Persistence\\Exception\\InvalidAggregatePersistenceException;',
            'use Kongono\\Persistence\\FlushResult;',
            'use Kongono\\Persistence\\LoadedAggregate;',
            'use Kongono\\Persistence\\RelationshipCardinality;',
            'use Kongono\\Persistence\\RelationshipCascadePolicy;',
            'use Kongono\\Persistence\\RelationshipLoad;',
        ];
        foreach ($repositories as $repository) {
            $lines[] = '';
            array_push($lines, ...$this->repository($repository));
        }

        return implode("\n", $lines) . "\n";
    }

    /** @return list<string> */
    private function repository(AggregateRepositoryDefinition $repository): array
    {
        $lines = [
            '/** Executes only explicit generated aggregate operations with caller-owned capabilities. */',
            'final class ' . $repository->className() . ' implements AggregateRepository',
            '{',
            '    public function aggregateName(): string',
            '    {',
            '        return ' . $this->literal($repository->aggregate()) . ';',
            '    }',
            '',
            '    public function rootType(): string',
            '    {',
            '        return \\' . $repository->rootType() . '::class;',
            '    }',
            '',
            '    public function loadPlans(): array',
            '    {',
            '        return [',
        ];
        foreach (array_keys($repository->loadPlans()) as $ordinal) {
            $lines[] = '            self::loadPlan' . sprintf('%04d', $ordinal + 1) . '(),';
        }
        array_push(
            $lines,
            '        ];',
            '    }',
            '',
            '    public function cascadePlan(): AggregateCascadePlan',
            '    {',
            '        return self::aggregateCascadePlan();',
            '    }',
        );
        foreach ($repository->loadPlans() as $ordinal => $plan) {
            array_push($lines, '', ...$this->loadMethod($plan, $ordinal + 1));
        }
        array_push(
            $lines,
            '',
            '    public function persist(LoadedAggregate $aggregate, DatabaseExecutor $database, AggregateCascadeExecutor $executor): FlushResult',
            '    {',
            '        self::assertCascadeAggregate($aggregate);',
            '        $plan = self::aggregateCascadePlan();',
            '',
            '        return $executor->execute($database, $plan, CascadeAction::Persist, $aggregate);',
            '    }',
            '',
            '    public function remove(LoadedAggregate $aggregate, DatabaseExecutor $database, AggregateCascadeExecutor $executor): FlushResult',
            '    {',
            '        self::assertCascadeAggregate($aggregate);',
            '        $plan = self::aggregateCascadePlan();',
            '',
            '        return $executor->execute($database, $plan, CascadeAction::Remove, $aggregate);',
            '    }',
        );
        foreach ($repository->loadPlans() as $ordinal => $plan) {
            array_push($lines, '', ...$this->loadPlanFactory($repository, $plan, $ordinal + 1));
        }
        array_push($lines, '', ...$this->cascadePlanFactory($repository));
        array_push(
            $lines,
            '',
            '    private static function assertCascadeAggregate(LoadedAggregate $aggregate): void',
            '    {',
            '        foreach ((new self())->loadPlans() as $plan) {',
            '            $expected = array_map(static fn(RelationshipLoad $load): string => $load->relationshipName(), $plan->relationships());',
            '            if (array_keys($aggregate->relationships()) === $expected) {',
            '                self::assertLoaded($aggregate, $plan);',
            '',
            '                return;',
            '            }',
            '        }',
            '        throw InvalidAggregatePersistenceException::invalid();',
            '    }',
            '',
            '    private static function assertLoaded(LoadedAggregate $aggregate, AggregateLoadPlan $plan): void',
            '    {',
            '        if ($aggregate->root()::class !== $plan->rootType()) {',
            '            throw InvalidAggregatePersistenceException::invalid();',
            '        }',
            '        $relationships = $aggregate->relationships();',
            '        $expected = array_map(static fn(RelationshipLoad $load): string => $load->relationshipName(), $plan->relationships());',
            '        if (array_keys($relationships) !== $expected) {',
            '            throw InvalidAggregatePersistenceException::invalid();',
            '        }',
            '        $total = 1;',
            '        foreach ($plan->relationships() as $load) {',
            '            $value = $relationships[$load->relationshipName()];',
            '            if (RelationshipCardinality::ToOne === $load->cardinality()) {',
            '                if (null === $value) {',
            '                    if (!$load->nullable()) {',
            '                        throw InvalidAggregatePersistenceException::invalid();',
            '                    }',
            '                    continue;',
            '                }',
            '                if (!is_object($value) || $value::class !== $load->targetType()) {',
            '                    throw InvalidAggregatePersistenceException::invalid();',
            '                }',
            '                ++$total;',
            '                continue;',
            '            }',
            '            if (!is_array($value) || count($value) > $load->maxRelated()) {',
            '                throw InvalidAggregatePersistenceException::invalid();',
            '            }',
            '            foreach ($value as $related) {',
            '                if ($related::class !== $load->targetType()) {',
            '                    throw InvalidAggregatePersistenceException::invalid();',
            '                }',
            '                ++$total;',
            '            }',
            '        }',
            '        if ($total > $plan->maxTotalObjects()) {',
            '            throw InvalidAggregatePersistenceException::invalid();',
            '        }',
            '    }',
            '}',
        );

        return $lines;
    }

    /** @return list<string> */
    private function loadMethod(LoadPlanDefinition $plan, int $ordinal): array
    {
        return [
            '    public function ' . $plan->method() . '(EntityIdentity $identity, DatabaseExecutor $database, AggregateLoadExecutor $executor): ?LoadedAggregate',
            '    {',
            '        $plan = self::loadPlan' . sprintf('%04d', $ordinal) . '();',
            '        $aggregate = $executor->load($database, $plan, $identity);',
            '        if (null === $aggregate) {',
            '            return null;',
            '        }',
            '        self::assertLoaded($aggregate, $plan);',
            '',
            '        return $aggregate;',
            '    }',
        ];
    }

    /** @return list<string> */
    private function loadPlanFactory(AggregateRepositoryDefinition $repository, LoadPlanDefinition $plan, int $ordinal): array
    {
        $lines = [
            '    private static function loadPlan' . sprintf('%04d', $ordinal) . '(): AggregateLoadPlan',
            '    {',
            '        return new AggregateLoadPlan(',
            '            ' . $this->literal($repository->aggregate()) . ',',
            '            ' . $this->literal($plan->method()) . ',',
            '            \\' . $repository->rootType() . '::class,',
            '            [',
        ];
        foreach ($plan->relationships() as $relationship) {
            array_push($lines, ...$this->relationshipLoad($relationship));
        }
        array_push(
            $lines,
            '            ],',
            '            ' . $plan->maxTotalObjects() . ',',
            '        );',
            '    }',
        );

        return $lines;
    }

    /** @return list<string> */
    private function relationshipLoad(RelationshipLoadDefinition $relationship): array
    {
        $lines = [
            '                new RelationshipLoad(',
            '                    ' . $this->literal($relationship->relationship()) . ',',
            '                    \\' . $relationship->targetType() . '::class,',
            '                    RelationshipCardinality::' . ('to_one' === $relationship->cardinality()->value ? 'ToOne' : 'ToMany') . ',',
            '                    ' . ($relationship->nullable() ? 'true' : 'false') . ',',
            '                    [',
        ];
        foreach ($relationship->order() as $entry) {
            $lines[] = '                        [' . $this->literal('field') . ' => ' . $this->literal($entry['field']) . ', ' . $this->literal('direction') . ' => ' . $this->literal($entry['direction']) . '],';
        }
        array_push(
            $lines,
            '                    ],',
            '                    ' . $relationship->maxRelated() . ',',
            '                ),',
        );

        return $lines;
    }

    /** @return list<string> */
    private function cascadePlanFactory(AggregateRepositoryDefinition $repository): array
    {
        $lines = [
            '    private static function aggregateCascadePlan(): AggregateCascadePlan',
            '    {',
            '        return new AggregateCascadePlan(',
            '            ' . $this->literal($repository->aggregate()) . ',',
            '            \\' . $repository->rootType() . '::class,',
            '            [',
        ];
        foreach ($repository->cascades() as $cascade) {
            $lines[] = '                new RelationshipCascadePolicy(' . $this->literal($cascade->relationship()) . ', \\' . $cascade->targetType() . '::class, ' . ($cascade->persist() ? 'true' : 'false') . ', ' . ($cascade->remove() ? 'true' : 'false') . '),';
        }
        array_push(
            $lines,
            '            ],',
            '            ' . $repository->maxChanges() . ',',
            '        );',
            '    }',
        );

        return $lines;
    }

    private function literal(string $value): string
    {
        return var_export($value, true);
    }
}
