<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Model\AggregateDefinition;
use Kongono\PersistenceBuild\Model\ConstructionDefinition;
use Kongono\PersistenceBuild\Model\ConstructionKind;
use Kongono\PersistenceBuild\Model\EntityDefinition;
use Kongono\PersistenceBuild\Model\FieldDefinition;
use Kongono\PersistenceBuild\Model\FieldRole;
use Kongono\PersistenceBuild\Model\FieldValueKind;
use Kongono\PersistenceBuild\Model\PersistenceModel;
use Kongono\PersistenceBuild\Model\RelationshipCardinality;
use Kongono\PersistenceBuild\Model\RelationshipDefinition;
use Kongono\PersistenceBuild\Model\RelationshipOrder;
use Kongono\PersistenceBuild\Model\RelationshipOwnership;
use Kongono\PersistenceBuild\Model\SortDirection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;

/** Compiles strict source plus current mapping evidence into a detached model. */
#[Api(ApiStatus::Internal)]
final class PersistenceModelCompiler
{
    public const MAX_ENTITIES = 256;
    public const MAX_FIELDS = 8192;
    public const MAX_AGGREGATES = 256;
    public const MAX_MEMBERS = 8192;
    public const MAX_RELATIONSHIPS = 512;
    public const MAX_ORDER_ENTRIES = 4096;
    public const MAX_IDENTIFIER_BYTES = 191;
    public const MAX_FQCN_BYTES = 255;

    /**
     * @param array<string, mixed> $source
     * @param array<string, mixed> $mappingArtifact
     * @return array{model:PersistenceModel,model_data:array<string, mixed>,snapshots:list<array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>}>}
     */
    public function compile(string $root, array $source, array $mappingArtifact): array
    {
        $root = PathGuard::resolveApplicationRoot($root);
        $this->assertKeys($source, ['schema', 'version', 'mapping_source', 'generated_namespace', 'entities', 'aggregates', 'relationships'], PersistenceBuildDiagnostic::InvalidSource);
        if ('kongono.persistence' !== ($source['schema'] ?? null) || 1 !== ($source['version'] ?? null)) {
            $this->fail(PersistenceBuildDiagnostic::InvalidSource, PersistenceBuildFailureCategory::SourceSchema);
        }
        $mappingPath = $this->relativeMappingPath($source['mapping_source'] ?? null);
        $namespace = $this->phpName($source['generated_namespace'] ?? null, false, PersistenceBuildDiagnostic::InvalidSource);
        $mappingModel = $this->object($mappingArtifact['model'] ?? null, PersistenceBuildDiagnostic::InvalidMappingEvidence);
        $this->assertKeys($mappingModel, ['schema', 'version', 'generated_namespace', 'mappings'], PersistenceBuildDiagnostic::InvalidMappingEvidence);
        if ('kongono.data-mapping' !== ($mappingModel['schema'] ?? null) || 1 !== ($mappingModel['version'] ?? null)) {
            $this->fail(PersistenceBuildDiagnostic::InvalidMappingEvidence, PersistenceBuildFailureCategory::MappingEvidence);
        }
        $mappingByRecord = $this->mappingByRecord($mappingModel['mappings'] ?? null);

        $entityValues = $this->list($source['entities'] ?? null, PersistenceBuildDiagnostic::InvalidSource);
        if ([] === $entityValues || count($entityValues) > self::MAX_ENTITIES) {
            $this->fail(PersistenceBuildDiagnostic::InvalidSource, PersistenceBuildFailureCategory::SourceBounds);
        }
        /** @var list<EntityDefinition> $entities */
        $entities = [];
        $snapshots = [];
        $seenEntities = [];
        $seenRecords = [];
        $totalFields = 0;
        foreach ($entityValues as $entityValue) {
            $declaration = $this->object($entityValue, PersistenceBuildDiagnostic::InvalidSource);
            $this->assertKeys($declaration, ['entity', 'storage_record', 'construction', 'identity', 'concurrency', 'fields'], PersistenceBuildDiagnostic::InvalidSource);
            $entityName = $this->phpName($declaration['entity'] ?? null, true, PersistenceBuildDiagnostic::InvalidConstruction);
            $recordName = $this->phpName($declaration['storage_record'] ?? null, true, PersistenceBuildDiagnostic::InvalidMappingEvidence);
            if (isset($seenEntities[$entityName]) || isset($seenRecords[$recordName]) || !isset($mappingByRecord[$recordName])) {
                $this->fail(PersistenceBuildDiagnostic::InvalidMappingEvidence, PersistenceBuildFailureCategory::MappingEvidence);
            }
            $seenEntities[$entityName] = true;
            $seenRecords[$recordName] = true;
            $mapping = $mappingByRecord[$recordName];
            $reflection = $this->entityReflection($entityName);
            $file = $reflection->getFileName();
            if (false === $file) {
                $this->fail(PersistenceBuildDiagnostic::InvalidConstruction, PersistenceBuildFailureCategory::Construction);
            }
            $snapshot = PathGuard::readDomainFile($root, $file);
            $snapshots[] = $snapshot;
            $construction = $this->construction($reflection, $declaration['construction'] ?? null);
            $method = ConstructionKind::Constructor === $construction->kind()
                ? $reflection->getConstructor()
                : $reflection->getMethod((string) $construction->method());
            if (!$method instanceof ReflectionMethod) {
                $this->fail(PersistenceBuildDiagnostic::InvalidConstruction, PersistenceBuildFailureCategory::Construction);
            }
            $fieldValues = $this->list($declaration['fields'] ?? null, PersistenceBuildDiagnostic::InvalidField);
            $mappingFields = $mapping['fields'];
            if ([] === $fieldValues || count($fieldValues) !== count($mappingFields)) {
                $this->fail(PersistenceBuildDiagnostic::InvalidField, PersistenceBuildFailureCategory::Field);
            }
            $totalFields += count($fieldValues);
            if ($totalFields > self::MAX_FIELDS) {
                $this->fail(PersistenceBuildDiagnostic::InvalidSource, PersistenceBuildFailureCategory::SourceBounds);
            }
            /** @var array<string, array{field:string,factory_parameter:string,accessor:string,role:FieldRole}> $sourceBindings */
            $sourceBindings = [];
            $fieldNames = [];
            $parameters = [];
            $accessors = [];
            foreach ($fieldValues as $fieldValue) {
                $binding = $this->object($fieldValue, PersistenceBuildDiagnostic::InvalidField);
                $this->assertKeys($binding, ['field', 'record_field', 'factory_parameter', 'accessor', 'role'], PersistenceBuildDiagnostic::InvalidField);
                $recordField = $this->identifier($binding['record_field'] ?? null, PersistenceBuildDiagnostic::InvalidField);
                $fieldName = $this->identifier($binding['field'] ?? null, PersistenceBuildDiagnostic::InvalidField);
                $parameter = $this->identifier($binding['factory_parameter'] ?? null, PersistenceBuildDiagnostic::InvalidField);
                $accessor = $this->identifier($binding['accessor'] ?? null, PersistenceBuildDiagnostic::InvalidField);
                $role = is_string($binding['role'] ?? null) ? FieldRole::tryFrom($binding['role']) : null;
                if (null === $role || isset($sourceBindings[$recordField]) || isset($fieldNames[$fieldName]) || isset($parameters[$parameter]) || isset($accessors[$accessor])) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidField, PersistenceBuildFailureCategory::Field);
                }
                $sourceBindings[$recordField] = [
                    'field' => $fieldName,
                    'factory_parameter' => $parameter,
                    'accessor' => $accessor,
                    'role' => $role,
                ];
                $fieldNames[$fieldName] = true;
                $parameters[$parameter] = true;
                $accessors[$accessor] = true;
            }
            $methodParameters = [];
            foreach ($method->getParameters() as $parameter) {
                if ($parameter->isVariadic() || $parameter->isPassedByReference() || null === $parameter->getType()) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidConstruction, PersistenceBuildFailureCategory::Construction);
                }
                $methodParameters[$parameter->getName()] = $parameter;
            }
            if (array_keys($parameters) !== array_keys(array_intersect_key($parameters, $methodParameters)) || count($methodParameters) !== count($parameters)) {
                $this->fail(PersistenceBuildDiagnostic::InvalidConstruction, PersistenceBuildFailureCategory::Construction);
            }
            $fields = [];
            foreach ($mappingFields as $mappingField) {
                $recordField = $mappingField['field'];
                if (!isset($sourceBindings[$recordField])) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidField, PersistenceBuildFailureCategory::Field);
                }
                $binding = $sourceBindings[$recordField];
                $kind = FieldValueKind::tryFrom($mappingField['kind']);
                if (null === $kind) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidField, PersistenceBuildFailureCategory::Field);
                }
                $parameter = $methodParameters[$binding['factory_parameter']];
                $this->assertCompatibleType($parameter->getType(), $kind, $mappingField['nullable'], PersistenceBuildDiagnostic::InvalidConstruction);
                $accessor = $binding['accessor'];
                if (!$reflection->hasMethod($accessor)) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidField, PersistenceBuildFailureCategory::Field);
                }
                $accessorMethod = $reflection->getMethod($accessor);
                if (!$accessorMethod->isPublic() || $accessorMethod->isStatic() || 0 !== $accessorMethod->getNumberOfParameters() || $accessorMethod->getDeclaringClass()->getName() !== $entityName) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidField, PersistenceBuildFailureCategory::Field);
                }
                $this->assertCompatibleType($accessorMethod->getReturnType(), $kind, $mappingField['nullable'], PersistenceBuildDiagnostic::InvalidField);
                $fields[] = new FieldDefinition(
                    $binding['field'],
                    $recordField,
                    $binding['factory_parameter'],
                    $accessor,
                    $binding['role'],
                    $kind,
                    $mappingField['nullable'],
                );
            }
            $identity = $this->identifier($declaration['identity'] ?? null, PersistenceBuildDiagnostic::InvalidField);
            $concurrency = $declaration['concurrency'] ?? null;
            if (null !== $concurrency) {
                $concurrency = $this->identifier($concurrency, PersistenceBuildDiagnostic::InvalidField);
            }
            $this->validateRoles($fields, $identity, $concurrency);
            $entities[] = new EntityDefinition(
                $entityName,
                $recordName,
                $construction,
                $identity,
                $concurrency,
                $fields,
                $snapshot['path'],
                $snapshot['size'],
                $snapshot['sha256'],
            );
        }
        usort($entities, static fn(EntityDefinition $left, EntityDefinition $right): int => strcmp($left->entity(), $right->entity()));
        usort($snapshots, static fn(array $left, array $right): int => strcmp($left['path'], $right['path']));

        $aggregates = $this->aggregates($source['aggregates'] ?? null, $entities);
        $relationships = $this->relationships($source['relationships'] ?? null, $entities, $aggregates);
        $model = new PersistenceModel($namespace, $entities, $aggregates, $relationships);

        return ['model' => $model, 'model_data' => $this->modelData($model), 'snapshots' => $snapshots];
    }

    /** @param array<string, mixed> $data */
    public function hydrateModel(array $data): PersistenceModel
    {
        $this->assertKeys($data, ['schema', 'version', 'generated_namespace', 'entities', 'aggregates', 'relationships'], PersistenceBuildDiagnostic::InvalidArtifact);
        if ('kongono.persistence-model' !== ($data['schema'] ?? null) || 1 !== ($data['version'] ?? null)) {
            $this->fail(PersistenceBuildDiagnostic::InvalidArtifact, PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $entities = [];
        foreach ($this->list($data['entities'] ?? null, PersistenceBuildDiagnostic::InvalidArtifact) as $value) {
            $item = $this->object($value, PersistenceBuildDiagnostic::InvalidArtifact);
            $this->assertKeys($item, ['entity', 'storage_record', 'construction', 'identity', 'concurrency', 'fields', 'source'], PersistenceBuildDiagnostic::InvalidArtifact);
            $constructionData = $this->object($item['construction'] ?? null, PersistenceBuildDiagnostic::InvalidArtifact);
            $this->assertKeys($constructionData, ['kind', 'method'], PersistenceBuildDiagnostic::InvalidArtifact);
            $kind = is_string($constructionData['kind'] ?? null) ? ConstructionKind::tryFrom($constructionData['kind']) : null;
            $method = $constructionData['method'] ?? null;
            if (null === $kind || (null !== $method && !is_string($method))) {
                $this->fail(PersistenceBuildDiagnostic::InvalidArtifact, PersistenceBuildFailureCategory::ArtifactStructure);
            }
            $fields = [];
            foreach ($this->list($item['fields'] ?? null, PersistenceBuildDiagnostic::InvalidArtifact) as $fieldValue) {
                $field = $this->object($fieldValue, PersistenceBuildDiagnostic::InvalidArtifact);
                $this->assertKeys($field, ['field', 'record_field', 'factory_parameter', 'accessor', 'role', 'kind', 'nullable'], PersistenceBuildDiagnostic::InvalidArtifact);
                $role = is_string($field['role'] ?? null) ? FieldRole::tryFrom($field['role']) : null;
                $valueKind = is_string($field['kind'] ?? null) ? FieldValueKind::tryFrom($field['kind']) : null;
                if (null === $role || null === $valueKind || !is_bool($field['nullable'] ?? null)) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidArtifact, PersistenceBuildFailureCategory::ArtifactStructure);
                }
                $fields[] = new FieldDefinition(
                    $this->artifactString($field['field'] ?? null),
                    $this->artifactString($field['record_field'] ?? null),
                    $this->artifactString($field['factory_parameter'] ?? null),
                    $this->artifactString($field['accessor'] ?? null),
                    $role,
                    $valueKind,
                    $field['nullable'],
                );
            }
            $source = $this->object($item['source'] ?? null, PersistenceBuildDiagnostic::InvalidArtifact);
            $this->assertKeys($source, ['path', 'bytes', 'sha256'], PersistenceBuildDiagnostic::InvalidArtifact);
            $entities[] = new EntityDefinition(
                $this->artifactString($item['entity'] ?? null),
                $this->artifactString($item['storage_record'] ?? null),
                new ConstructionDefinition($kind, $method),
                $this->artifactString($item['identity'] ?? null),
                null === ($item['concurrency'] ?? null) ? null : $this->artifactString($item['concurrency']),
                $fields,
                $this->artifactString($source['path'] ?? null),
                $this->artifactInteger($source['bytes'] ?? null),
                $this->digest($source['sha256'] ?? null),
            );
        }
        $aggregates = [];
        foreach ($this->list($data['aggregates'] ?? null, PersistenceBuildDiagnostic::InvalidArtifact) as $value) {
            $item = $this->object($value, PersistenceBuildDiagnostic::InvalidArtifact);
            $this->assertKeys($item, ['name', 'root', 'members'], PersistenceBuildDiagnostic::InvalidArtifact);
            $members = [];
            foreach ($this->list($item['members'] ?? null, PersistenceBuildDiagnostic::InvalidArtifact) as $member) {
                $members[] = $this->artifactString($member);
            }
            $aggregates[] = new AggregateDefinition($this->artifactString($item['name'] ?? null), $this->artifactString($item['root'] ?? null), $members);
        }
        $relationships = [];
        foreach ($this->list($data['relationships'] ?? null, PersistenceBuildDiagnostic::InvalidArtifact) as $value) {
            $item = $this->object($value, PersistenceBuildDiagnostic::InvalidArtifact);
            $this->assertKeys($item, ['name', 'source', 'target', 'source_field', 'target_field', 'cardinality', 'nullable', 'ownership', 'order'], PersistenceBuildDiagnostic::InvalidArtifact);
            $cardinality = is_string($item['cardinality'] ?? null) ? RelationshipCardinality::tryFrom($item['cardinality']) : null;
            $ownership = is_string($item['ownership'] ?? null) ? RelationshipOwnership::tryFrom($item['ownership']) : null;
            if (null === $cardinality || null === $ownership || !is_bool($item['nullable'] ?? null)) {
                $this->fail(PersistenceBuildDiagnostic::InvalidArtifact, PersistenceBuildFailureCategory::ArtifactStructure);
            }
            $order = [];
            foreach ($this->list($item['order'] ?? null, PersistenceBuildDiagnostic::InvalidArtifact) as $orderValue) {
                $entry = $this->object($orderValue, PersistenceBuildDiagnostic::InvalidArtifact);
                $this->assertKeys($entry, ['field', 'direction'], PersistenceBuildDiagnostic::InvalidArtifact);
                $direction = is_string($entry['direction'] ?? null) ? SortDirection::tryFrom($entry['direction']) : null;
                if (null === $direction) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidArtifact, PersistenceBuildFailureCategory::ArtifactStructure);
                }
                $order[] = new RelationshipOrder($this->artifactString($entry['field'] ?? null), $direction);
            }
            $relationships[] = new RelationshipDefinition(
                $this->artifactString($item['name'] ?? null),
                $this->artifactString($item['source'] ?? null),
                $this->artifactString($item['target'] ?? null),
                $this->artifactString($item['source_field'] ?? null),
                $this->artifactString($item['target_field'] ?? null),
                $cardinality,
                $item['nullable'],
                $ownership,
                $order,
            );
        }

        return new PersistenceModel($this->artifactString($data['generated_namespace'] ?? null), $entities, $aggregates, $relationships);
    }

    /** @return array<string, mixed> */
    public function modelData(PersistenceModel $model): array
    {
        return [
            'schema' => 'kongono.persistence-model',
            'version' => 1,
            'generated_namespace' => $model->generatedNamespace(),
            'entities' => array_map(static fn(EntityDefinition $entity): array => [
                'entity' => $entity->entity(),
                'storage_record' => $entity->storageRecord(),
                'construction' => ['kind' => $entity->construction()->kind()->value, 'method' => $entity->construction()->method()],
                'identity' => $entity->identity(),
                'concurrency' => $entity->concurrency(),
                'fields' => array_map(static fn(FieldDefinition $field): array => [
                    'field' => $field->field(),
                    'record_field' => $field->recordField(),
                    'factory_parameter' => $field->factoryParameter(),
                    'accessor' => $field->accessor(),
                    'role' => $field->role()->value,
                    'kind' => $field->valueKind()->value,
                    'nullable' => $field->nullable(),
                ], $entity->fields()),
                'source' => ['path' => $entity->sourcePath(), 'bytes' => $entity->sourceBytes(), 'sha256' => $entity->sourceSha256()],
            ], $model->entities()),
            'aggregates' => array_map(static fn(AggregateDefinition $aggregate): array => [
                'name' => $aggregate->name(),
                'root' => $aggregate->root(),
                'members' => $aggregate->members(),
            ], $model->aggregates()),
            'relationships' => array_map(static fn(RelationshipDefinition $relationship): array => [
                'name' => $relationship->name(),
                'source' => $relationship->source(),
                'target' => $relationship->target(),
                'source_field' => $relationship->sourceField(),
                'target_field' => $relationship->targetField(),
                'cardinality' => $relationship->cardinality()->value,
                'nullable' => $relationship->nullable(),
                'ownership' => $relationship->ownership()->value,
                'order' => array_map(static fn(RelationshipOrder $order): array => ['field' => $order->field(), 'direction' => $order->direction()->value], $relationship->order()),
            ], $model->relationships()),
        ];
    }

    /** @return array<string, array{fields:list<array{field:string,kind:string,nullable:bool}>}> */
    private function mappingByRecord(mixed $value): array
    {
        $mappings = $this->list($value, PersistenceBuildDiagnostic::InvalidMappingEvidence);
        $result = [];
        foreach ($mappings as $mappingValue) {
            $mapping = $this->object($mappingValue, PersistenceBuildDiagnostic::InvalidMappingEvidence);
            $record = $this->phpName($mapping['record'] ?? null, true, PersistenceBuildDiagnostic::InvalidMappingEvidence);
            $fields = [];
            foreach ($this->list($mapping['fields'] ?? null, PersistenceBuildDiagnostic::InvalidMappingEvidence) as $fieldValue) {
                $field = $this->object($fieldValue, PersistenceBuildDiagnostic::InvalidMappingEvidence);
                if (!is_string($field['field'] ?? null) || !is_string($field['kind'] ?? null) || !is_bool($field['nullable'] ?? null)) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidMappingEvidence, PersistenceBuildFailureCategory::MappingEvidence);
                }
                $fields[] = ['field' => $field['field'], 'kind' => $field['kind'], 'nullable' => $field['nullable']];
            }
            $result[$record] = ['fields' => $fields];
        }

        return $result;
    }

    /** @param ReflectionClass<object> $reflection */
    private function construction(ReflectionClass $reflection, mixed $value): ConstructionDefinition
    {
        $data = $this->object($value, PersistenceBuildDiagnostic::InvalidConstruction);
        $this->assertKeys($data, ['kind', 'method'], PersistenceBuildDiagnostic::InvalidConstruction);
        $kind = is_string($data['kind'] ?? null) ? ConstructionKind::tryFrom($data['kind']) : null;
        $methodName = $data['method'] ?? null;
        if (null === $kind || (null !== $methodName && !is_string($methodName))) {
            $this->fail(PersistenceBuildDiagnostic::InvalidConstruction, PersistenceBuildFailureCategory::Construction);
        }
        if (ConstructionKind::Constructor === $kind) {
            $method = $reflection->getConstructor();
            if (null !== $methodName || null === $method || !$method->isPublic() || $method->getDeclaringClass()->getName() !== $reflection->getName()) {
                $this->fail(PersistenceBuildDiagnostic::InvalidConstruction, PersistenceBuildFailureCategory::Construction);
            }
        } else {
            if (!is_string($methodName) || !$this->validIdentifier($methodName) || !$reflection->hasMethod($methodName)) {
                $this->fail(PersistenceBuildDiagnostic::InvalidConstruction, PersistenceBuildFailureCategory::Construction);
            }
            $method = $reflection->getMethod($methodName);
            if (!$method->isPublic() || !$method->isStatic() || $method->getDeclaringClass()->getName() !== $reflection->getName()) {
                $this->fail(PersistenceBuildDiagnostic::InvalidConstruction, PersistenceBuildFailureCategory::Construction);
            }
        }

        return new ConstructionDefinition($kind, $methodName);
    }

    /** @return ReflectionClass<object> */
    private function entityReflection(string $entity): ReflectionClass
    {
        if (!class_exists($entity)) {
            $this->fail(PersistenceBuildDiagnostic::InvalidConstruction, PersistenceBuildFailureCategory::Construction);
        }
        $reflection = new ReflectionClass($entity);
        if ($reflection->isInternal() || $reflection->isAnonymous() || $reflection->isInterface() || $reflection->isTrait() || $reflection->isEnum()) {
            $this->fail(PersistenceBuildDiagnostic::InvalidConstruction, PersistenceBuildFailureCategory::Construction);
        }

        return $reflection;
    }

    /** @param list<FieldDefinition> $fields */
    private function validateRoles(array $fields, string $identity, ?string $concurrency): void
    {
        $identityCount = 0;
        $concurrencyCount = 0;
        foreach ($fields as $field) {
            if (FieldRole::Identity === $field->role()) {
                ++$identityCount;
                if ($field->field() !== $identity) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidField, PersistenceBuildFailureCategory::Field);
                }
            }
            if (FieldRole::Concurrency === $field->role()) {
                ++$concurrencyCount;
                if ($field->field() !== $concurrency) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidField, PersistenceBuildFailureCategory::Field);
                }
            }
        }
        if (1 !== $identityCount || (null === $concurrency ? 0 !== $concurrencyCount : 1 !== $concurrencyCount)) {
            $this->fail(PersistenceBuildDiagnostic::InvalidField, PersistenceBuildFailureCategory::Field);
        }
    }

    /**
     * @param list<EntityDefinition> $entities
     * @return list<AggregateDefinition>
     */
    private function aggregates(mixed $value, array $entities): array
    {
        $values = $this->list($value, PersistenceBuildDiagnostic::InvalidAggregate);
        if ([] === $values || count($values) > self::MAX_AGGREGATES) {
            $this->fail(PersistenceBuildDiagnostic::InvalidAggregate, PersistenceBuildFailureCategory::Aggregate);
        }
        $known = array_fill_keys(array_map(static fn(EntityDefinition $entity): string => $entity->entity(), $entities), true);
        $membership = [];
        $names = [];
        /** @var list<AggregateDefinition> $aggregates */
        $aggregates = [];
        $total = 0;
        foreach ($values as $valueItem) {
            $item = $this->object($valueItem, PersistenceBuildDiagnostic::InvalidAggregate);
            $this->assertKeys($item, ['name', 'root', 'members'], PersistenceBuildDiagnostic::InvalidAggregate);
            $name = $this->identifier($item['name'] ?? null, PersistenceBuildDiagnostic::InvalidAggregate);
            $root = $this->phpName($item['root'] ?? null, true, PersistenceBuildDiagnostic::InvalidAggregate);
            $memberValues = $this->list($item['members'] ?? null, PersistenceBuildDiagnostic::InvalidAggregate);
            if (isset($names[$name]) || [] === $memberValues) {
                $this->fail(PersistenceBuildDiagnostic::InvalidAggregate, PersistenceBuildFailureCategory::Aggregate);
            }
            $names[$name] = true;
            $members = [];
            foreach ($memberValues as $memberValue) {
                $member = $this->phpName($memberValue, true, PersistenceBuildDiagnostic::InvalidAggregate);
                if (!isset($known[$member]) || isset($membership[$member]) || in_array($member, $members, true)) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidAggregate, PersistenceBuildFailureCategory::Aggregate);
                }
                $membership[$member] = $name;
                $members[] = $member;
            }
            $total += count($members);
            if ($total > self::MAX_MEMBERS || !in_array($root, $members, true)) {
                $this->fail(PersistenceBuildDiagnostic::InvalidAggregate, PersistenceBuildFailureCategory::Aggregate);
            }
            $aggregates[] = new AggregateDefinition($name, $root, $members);
        }
        if (count($membership) !== count($known)) {
            $this->fail(PersistenceBuildDiagnostic::InvalidAggregate, PersistenceBuildFailureCategory::Aggregate);
        }
        usort($aggregates, static fn(AggregateDefinition $left, AggregateDefinition $right): int => strcmp($left->name(), $right->name()));

        return $aggregates;
    }

    /**
     * @param list<EntityDefinition> $entities
     * @param list<AggregateDefinition> $aggregates
     * @return list<RelationshipDefinition>
     */
    private function relationships(mixed $value, array $entities, array $aggregates): array
    {
        $values = $this->list($value, PersistenceBuildDiagnostic::InvalidRelationship);
        if (count($values) > self::MAX_RELATIONSHIPS) {
            $this->fail(PersistenceBuildDiagnostic::InvalidRelationship, PersistenceBuildFailureCategory::Relationship);
        }
        $entityMap = [];
        foreach ($entities as $entity) {
            $entityMap[$entity->entity()] = $entity;
        }
        $aggregateByEntity = [];
        foreach ($aggregates as $aggregate) {
            foreach ($aggregate->members() as $member) {
                $aggregateByEntity[$member] = $aggregate->name();
            }
        }
        $seen = [];
        /** @var list<RelationshipDefinition> $result */
        $result = [];
        $totalOrder = 0;
        foreach ($values as $valueItem) {
            $item = $this->object($valueItem, PersistenceBuildDiagnostic::InvalidRelationship);
            $this->assertKeys($item, ['name', 'source', 'target', 'source_field', 'target_field', 'cardinality', 'nullable', 'ownership', 'order'], PersistenceBuildDiagnostic::InvalidRelationship);
            $name = $this->identifier($item['name'] ?? null, PersistenceBuildDiagnostic::InvalidRelationship);
            $source = $this->phpName($item['source'] ?? null, true, PersistenceBuildDiagnostic::InvalidRelationship);
            $target = $this->phpName($item['target'] ?? null, true, PersistenceBuildDiagnostic::InvalidRelationship);
            $sourceFieldName = $this->identifier($item['source_field'] ?? null, PersistenceBuildDiagnostic::InvalidRelationship);
            $targetFieldName = $this->identifier($item['target_field'] ?? null, PersistenceBuildDiagnostic::InvalidRelationship);
            $cardinality = is_string($item['cardinality'] ?? null) ? RelationshipCardinality::tryFrom($item['cardinality']) : null;
            $ownership = is_string($item['ownership'] ?? null) ? RelationshipOwnership::tryFrom($item['ownership']) : null;
            if (isset($seen[$name]) || !isset($entityMap[$source], $entityMap[$target]) || null === $cardinality || null === $ownership || !is_bool($item['nullable'] ?? null)) {
                $this->fail(PersistenceBuildDiagnostic::InvalidRelationship, PersistenceBuildFailureCategory::Relationship);
            }
            $sourceField = $entityMap[$source]->field($sourceFieldName);
            $targetField = $entityMap[$target]->field($targetFieldName);
            if (null === $sourceField || null === $targetField || $sourceField->valueKind() !== $targetField->valueKind()) {
                $this->fail(PersistenceBuildDiagnostic::InvalidRelationship, PersistenceBuildFailureCategory::Relationship);
            }
            if (RelationshipOwnership::Aggregate === $ownership && $aggregateByEntity[$source] !== $aggregateByEntity[$target]) {
                $this->fail(PersistenceBuildDiagnostic::InvalidRelationship, PersistenceBuildFailureCategory::Relationship);
            }
            $order = [];
            foreach ($this->list($item['order'] ?? null, PersistenceBuildDiagnostic::InvalidRelationship) as $orderValue) {
                $entry = $this->object($orderValue, PersistenceBuildDiagnostic::InvalidRelationship);
                $this->assertKeys($entry, ['field', 'direction'], PersistenceBuildDiagnostic::InvalidRelationship);
                $field = $this->identifier($entry['field'] ?? null, PersistenceBuildDiagnostic::InvalidRelationship);
                $direction = is_string($entry['direction'] ?? null) ? SortDirection::tryFrom($entry['direction']) : null;
                if (null === $direction || null === $entityMap[$target]->field($field)) {
                    $this->fail(PersistenceBuildDiagnostic::InvalidRelationship, PersistenceBuildFailureCategory::Relationship);
                }
                $order[] = new RelationshipOrder($field, $direction);
            }
            $totalOrder += count($order);
            if ($totalOrder > self::MAX_ORDER_ENTRIES || (RelationshipCardinality::ToOne === $cardinality ? [] !== $order : [] === $order)) {
                $this->fail(PersistenceBuildDiagnostic::InvalidRelationship, PersistenceBuildFailureCategory::Relationship);
            }
            $seen[$name] = true;
            $result[] = new RelationshipDefinition($name, $source, $target, $sourceFieldName, $targetFieldName, $cardinality, $item['nullable'], $ownership, $order);
        }
        usort($result, static fn(RelationshipDefinition $left, RelationshipDefinition $right): int => strcmp($left->name(), $right->name()));

        return $result;
    }

    private function assertCompatibleType(?ReflectionType $type, FieldValueKind $kind, bool $nullable, PersistenceBuildDiagnostic $diagnostic): void
    {
        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin() || $type->allowsNull() !== $nullable) {
            $this->fail($diagnostic, PersistenceBuildDiagnostic::InvalidConstruction === $diagnostic ? PersistenceBuildFailureCategory::Construction : PersistenceBuildFailureCategory::Field);
        }
        $expected = match ($kind) {
            FieldValueKind::Boolean => 'bool',
            FieldValueKind::Integer => 'int',
            FieldValueKind::Float => 'float',
            FieldValueKind::Text, FieldValueKind::Binary => 'string',
        };
        if ($type->getName() !== $expected) {
            $this->fail($diagnostic, PersistenceBuildDiagnostic::InvalidConstruction === $diagnostic ? PersistenceBuildFailureCategory::Construction : PersistenceBuildFailureCategory::Field);
        }
    }

    private function relativeMappingPath(mixed $value): string
    {
        if (!is_string($value) || PathGuard::MAPPING_SOURCE_BASENAME !== basename($value) || str_contains($value, '\\') || str_starts_with($value, '/') || str_contains($value, ':')) {
            $this->fail(PersistenceBuildDiagnostic::InvalidMappingEvidence, PersistenceBuildFailureCategory::MappingEvidence);
        }
        foreach (explode('/', $value) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                $this->fail(PersistenceBuildDiagnostic::InvalidMappingEvidence, PersistenceBuildFailureCategory::MappingEvidence);
            }
        }

        return $value;
    }

    private function phpName(mixed $value, bool $class, PersistenceBuildDiagnostic $diagnostic): string
    {
        $maximum = $class ? self::MAX_FQCN_BYTES : 255;
        if (!is_string($value) || '' === $value || strlen($value) > $maximum || 1 !== preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*)(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $value)) {
            $this->fail($diagnostic, PersistenceBuildFailureCategory::SourceSchema);
        }
        $reserved = ['self', 'parent', 'static', 'int', 'float', 'string', 'bool', 'array', 'object', 'mixed', 'void', 'never', 'null', 'false', 'true', 'iterable', 'callable', 'resource'];
        foreach (explode('\\', $value) as $segment) {
            if (in_array(strtolower($segment), $reserved, true)) {
                $this->fail($diagnostic, PersistenceBuildFailureCategory::SourceSchema);
            }
        }

        return $value;
    }

    private function identifier(mixed $value, PersistenceBuildDiagnostic $diagnostic): string
    {
        if (!is_string($value) || !$this->validIdentifier($value)) {
            $this->fail($diagnostic, PersistenceBuildFailureCategory::SourceSchema);
        }

        return $value;
    }

    private function validIdentifier(string $value): bool
    {
        return '' !== $value && strlen($value) <= self::MAX_IDENTIFIER_BYTES && 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, PersistenceBuildDiagnostic $diagnostic): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->fail($diagnostic, PersistenceBuildFailureCategory::SourceSchema);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return list<mixed> */
    private function list(mixed $value, PersistenceBuildDiagnostic $diagnostic): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->fail($diagnostic, PersistenceBuildFailureCategory::SourceSchema);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $keys
     */
    private function assertKeys(array $value, array $keys, PersistenceBuildDiagnostic $diagnostic): void
    {
        $actual = array_keys($value);
        sort($actual);
        sort($keys);
        if ($actual !== $keys) {
            $this->fail($diagnostic, PersistenceBuildFailureCategory::SourceSchema);
        }
    }

    private function artifactString(mixed $value): string
    {
        if (!is_string($value) || '' === $value) {
            $this->fail(PersistenceBuildDiagnostic::InvalidArtifact, PersistenceBuildFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    private function artifactInteger(mixed $value): int
    {
        if (!is_int($value) || $value < 1 || $value > PathGuard::MAX_SOURCE_BYTES) {
            $this->fail(PersistenceBuildDiagnostic::InvalidArtifact, PersistenceBuildFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $this->fail(PersistenceBuildDiagnostic::InvalidArtifact, PersistenceBuildFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    private function fail(PersistenceBuildDiagnostic $diagnostic, PersistenceBuildFailureCategory $category): never
    {
        throw InvalidPersistenceSourceException::invalid($diagnostic, $category);
    }
}
