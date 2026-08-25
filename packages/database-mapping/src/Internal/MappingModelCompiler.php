<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Exception\InvalidMappingSourceException;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

/** Compiles the closed source vocabulary after exact record-contract inspection. */
#[Api(ApiStatus::Internal)]
final class MappingModelCompiler
{
    private const ROOT_KEYS = ['generated_namespace', 'mappings', 'schema', 'version'];
    private const MAPPING_KEYS = ['fields', 'identity', 'mapper', 'record', 'repository', 'table'];
    private const FIELD_KEYS = ['accessor', 'column', 'constructor_parameter', 'field', 'kind', 'nullable'];

    /**
     * @param array<string, mixed> $source
     * @return array{model:array<string, mixed>,sources:list<array{path:string,bytes:int,sha256:string}>,snapshots:list<array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>}>}
     */
    public function compile(string $root, array $source): array
    {
        $this->assertKeys($source, self::ROOT_KEYS);
        if ('kongono.data-mapping' !== ($source['schema'] ?? null) || 1 !== ($source['version'] ?? null)) {
            $this->invalid(MappingFailureCategory::SourceSchema);
        }
        $namespace = $this->qualifiedName($source['generated_namespace'] ?? null, false);
        $mappings = $this->list($source['mappings'] ?? null);
        if ([] === $mappings || count($mappings) > 256) {
            $this->invalid(MappingFailureCategory::MappingBounds);
        }

        $compiled = [];
        $recordNames = [];
        $generatedNames = [];
        $tables = [];
        $snapshotByPath = [];
        $totalFields = 0;
        foreach ($mappings as $mappingValue) {
            $mapping = $this->object($mappingValue);
            $this->assertKeys($mapping, self::MAPPING_KEYS);
            $record = $this->qualifiedName($mapping['record'] ?? null, true);
            $mapper = $this->identifier($mapping['mapper'] ?? null, 255);
            $repository = $this->identifier($mapping['repository'] ?? null, 255);
            if ($mapper === $repository) {
                $this->invalid(MappingFailureCategory::MappingDuplicate);
            }
            $mapperClass = $namespace . '\\' . $mapper;
            $repositoryClass = $namespace . '\\' . $repository;
            $this->unique($recordNames, $record);
            $this->unique($generatedNames, $mapperClass);
            $this->unique($generatedNames, $repositoryClass);
            if ($this->symbolExists($mapperClass) || $this->symbolExists($repositoryClass)) {
                $this->invalid(MappingFailureCategory::MappingName);
            }

            $tableValues = $this->list($mapping['table'] ?? null);
            if ([] === $tableValues || count($tableValues) > 3) {
                $this->invalid(MappingFailureCategory::MappingBounds);
            }
            $table = [];
            foreach ($tableValues as $segment) {
                if (!is_string($segment)) {
                    $this->invalid(MappingFailureCategory::MappingName);
                }
                try {
                    new SqlIdentifier($segment);
                } catch (Throwable) {
                    $this->invalid(MappingFailureCategory::MappingName);
                }
                $table[] = $segment;
            }
            try {
                new SqlQualifiedName(array_map(static fn(string $segment): SqlIdentifier => new SqlIdentifier($segment), $table));
            } catch (Throwable) {
                $this->invalid(MappingFailureCategory::MappingName);
            }
            $this->unique($tables, implode("\0", $table));

            $identity = $this->identifier($mapping['identity'] ?? null, 128);
            $fields = $this->list($mapping['fields'] ?? null);
            if ([] === $fields || count($fields) > 256) {
                $this->invalid(MappingFailureCategory::MappingBounds);
            }
            $totalFields += count($fields);
            if ($totalFields > 8192) {
                $this->invalid(MappingFailureCategory::MappingBounds);
            }

            $compiledFields = [];
            $fieldNames = [];
            $columns = [];
            $constructorNames = [];
            $accessors = [];
            $identityOrdinal = null;
            foreach ($fields as $ordinal => $fieldValue) {
                $field = $this->object($fieldValue);
                $this->assertKeys($field, self::FIELD_KEYS);
                $fieldName = $this->identifier($field['field'] ?? null, 128);
                $column = $this->sqlIdentifier($field['column'] ?? null);
                $kind = $field['kind'] ?? null;
                if (!is_string($kind) || !in_array($kind, ['boolean', 'integer', 'float', 'text', 'binary'], true)) {
                    $this->invalid(MappingFailureCategory::RecordContract);
                }
                $nullable = $field['nullable'] ?? null;
                if (!is_bool($nullable)) {
                    $this->invalid(MappingFailureCategory::RecordContract);
                }
                $constructorParameter = $this->identifier($field['constructor_parameter'] ?? null, 128);
                $accessor = $this->identifier($field['accessor'] ?? null, 128);
                $this->unique($fieldNames, $fieldName);
                $this->unique($columns, $column);
                $this->unique($constructorNames, $constructorParameter);
                $this->unique($accessors, $accessor);
                if ($identity === $fieldName) {
                    $identityOrdinal = $ordinal;
                    if ($nullable || !in_array($kind, ['integer', 'text'], true)) {
                        $this->invalid(MappingFailureCategory::RecordContract);
                    }
                }
                $compiledFields[] = [
                    'field' => $fieldName,
                    'column' => $column,
                    'kind' => $kind,
                    'nullable' => $nullable,
                    'constructor_parameter' => $constructorParameter,
                    'accessor' => $accessor,
                ];
            }
            if (null === $identityOrdinal || count($compiledFields) < 2) {
                $this->invalid(MappingFailureCategory::RecordContract);
            }

            $snapshot = $this->validateRecord($root, $record, $compiledFields);
            $snapshotByPath[$snapshot['path']] = $snapshot;
            $compiled[] = [
                'record' => $record,
                'mapper' => $mapper,
                'repository' => $repository,
                'table' => $table,
                'identity' => $identity,
                'identity_ordinal' => $identityOrdinal,
                'fields' => $compiledFields,
            ];
        }

        usort($compiled, static fn(array $left, array $right): int => strcmp($left['record'], $right['record']));
        ksort($snapshotByPath, SORT_STRING);
        $sources = [];
        foreach ($snapshotByPath as $snapshot) {
            $sources[] = ['path' => $snapshot['path'], 'bytes' => $snapshot['size'], 'sha256' => $snapshot['sha256']];
        }

        return [
            'model' => [
                'schema' => 'kongono.data-mapping',
                'version' => 1,
                'generated_namespace' => $namespace,
                'mappings' => $compiled,
            ],
            'sources' => $sources,
            'snapshots' => array_values($snapshotByPath),
        ];
    }

    /**
     * @param list<array{field:string,column:string,kind:string,nullable:bool,constructor_parameter:string,accessor:string}> $fields
     * @return array{absolute:string,path:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>}
     */
    private function validateRecord(string $root, string $record, array $fields): array
    {
        if (!class_exists($record)) {
            $this->invalid(MappingFailureCategory::RecordContract);
        }
        $class = new ReflectionClass($record);
        if ($class->getName() !== $record || !$class->isFinal() || !$class->isReadOnly() || $class->isAnonymous()) {
            $this->invalid(MappingFailureCategory::RecordContract);
        }
        $constructor = $class->getConstructor();
        if (
            !$constructor instanceof ReflectionMethod
            || !$constructor->isPublic()
            || $constructor->isStatic()
            || $constructor->getDeclaringClass()->getName() !== $record
        ) {
            $this->invalid(MappingFailureCategory::RecordContract);
        }
        $parameters = $constructor->getParameters();
        if (count($parameters) !== count($fields)) {
            $this->invalid(MappingFailureCategory::RecordContract);
        }
        foreach ($fields as $ordinal => $field) {
            $parameter = $parameters[$ordinal];
            if (
                $parameter->getName() !== $field['constructor_parameter']
                || $parameter->isVariadic()
                || $parameter->isPassedByReference()
                || $parameter->isDefaultValueAvailable()
                || !$this->typeMatches($parameter, $field['kind'], $field['nullable'])
            ) {
                $this->invalid(MappingFailureCategory::RecordContract);
            }
            if (!$class->hasMethod($field['accessor'])) {
                $this->invalid(MappingFailureCategory::RecordContract);
            }
            $accessor = $class->getMethod($field['accessor']);
            if (
                !$accessor->isPublic()
                || $accessor->isStatic()
                || $accessor->returnsReference()
                || 0 !== $accessor->getNumberOfParameters()
                || $accessor->getDeclaringClass()->getName() !== $record
                || !$this->returnTypeMatches($accessor, $field['kind'], $field['nullable'])
            ) {
                $this->invalid(MappingFailureCategory::RecordContract);
            }
        }
        $filename = $class->getFileName();
        if (!is_string($filename)) {
            $this->invalid(MappingFailureCategory::Provenance);
        }

        return PathGuard::readAbsoluteDeclaringFile($root, $filename);
    }

    private function typeMatches(ReflectionParameter $parameter, string $kind, bool $nullable): bool
    {
        $type = $parameter->getType();

        return $type instanceof ReflectionNamedType
            && $type->isBuiltin()
            && $type->getName() === $this->nativeType($kind)
            && $type->allowsNull() === $nullable;
    }

    private function returnTypeMatches(ReflectionMethod $method, string $kind, bool $nullable): bool
    {
        $type = $method->getReturnType();

        return $type instanceof ReflectionNamedType
            && $type->isBuiltin()
            && $type->getName() === $this->nativeType($kind)
            && $type->allowsNull() === $nullable;
    }

    private function nativeType(string $kind): string
    {
        return match ($kind) {
            'boolean' => 'bool',
            'integer' => 'int',
            'float' => 'float',
            'text', 'binary' => 'string',
            default => throw InvalidMappingSourceException::invalid(MappingFailureCategory::RecordContract),
        };
    }

    private function qualifiedName(mixed $value, bool $requireNamespace): string
    {
        if (
            !is_string($value)
            || '' === $value
            || strlen($value) > 255
            || str_starts_with($value, '\\')
            || ($requireNamespace && !str_contains($value, '\\'))
            || 1 !== preg_match('/^(?:[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*\\\\)*[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/D', $value)
        ) {
            $this->invalid(MappingFailureCategory::MappingName);
        }
        foreach (explode('\\', $value) as $segment) {
            if (in_array(strtolower($segment), ['self', 'parent', 'static'], true)) {
                $this->invalid(MappingFailureCategory::MappingName);
            }
        }

        return $value;
    }

    private function identifier(mixed $value, int $limit): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > $limit || 1 !== preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/D', $value)) {
            $this->invalid(MappingFailureCategory::MappingName);
        }

        return $value;
    }

    private function sqlIdentifier(mixed $value): string
    {
        if (!is_string($value)) {
            $this->invalid(MappingFailureCategory::MappingName);
        }
        try {
            new SqlIdentifier($value);
        } catch (Throwable) {
            $this->invalid(MappingFailureCategory::MappingName);
        }

        return $value;
    }

    /** @param array<string, true> $seen */
    private function unique(array &$seen, string $value): void
    {
        $key = 'value:' . $value;
        if (isset($seen[$key])) {
            $this->invalid(MappingFailureCategory::MappingDuplicate);
        }
        $seen[$key] = true;
    }

    private function symbolExists(string $symbol): bool
    {
        return class_exists($symbol) || interface_exists($symbol) || trait_exists($symbol) || enum_exists($symbol);
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid(MappingFailureCategory::SourceSchema);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid(MappingFailureCategory::SourceSchema);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $expected
     */
    private function assertKeys(array $value, array $expected): void
    {
        $keys = array_keys($value);
        sort($keys, SORT_STRING);
        if ($keys !== $expected) {
            $this->invalid(MappingFailureCategory::SourceSchema);
        }
    }

    private function invalid(MappingFailureCategory $category): never
    {
        throw InvalidMappingSourceException::invalid($category);
    }
}
