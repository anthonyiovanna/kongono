<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Emits canonical ordinary PHP for validated mapping models. */
#[Api(ApiStatus::Internal)]
final class GeneratedDataAccessEmitter
{
    /**
     * @param array<string, mixed> $model
     */
    public function emit(
        array $model,
        string $artifactSha256,
        string $modelSha256,
        string $implementationSha256,
    ): string {
        /** @var string $namespace */
        $namespace = $model['generated_namespace'];
        /** @var list<array<string, mixed>> $mappings */
        $mappings = $model['mappings'];
        $classes = [];
        foreach ($mappings as $mapping) {
            /** @var string $record */
            $record = $mapping['record'];
            /** @var string $mapper */
            $mapper = $mapping['mapper'];
            /** @var string $repository */
            $repository = $mapping['repository'];
            $classes[] = [
                'record' => $record,
                'mapper' => $namespace . '\\' . $mapper,
                'repository' => $namespace . '\\' . $repository,
            ];
        }
        $metadata = CanonicalJson::inline([
            'schema' => 'kongono.generated-data-access',
            'version' => 1,
            'mapping_artifact_sha256' => $artifactSha256,
            'model_sha256' => $modelSha256,
            'generator_implementation_sha256' => $implementationSha256,
            'generated_namespace' => $namespace,
            'classes' => $classes,
        ]);

        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '// ' . $metadata,
            '',
            'namespace ' . $namespace . ';',
            '',
            'use Kongono\\Database\\CommandResult;',
            'use Kongono\\Database\\DatabaseExecutor;',
            'use Kongono\\Database\\DatabaseRow;',
            'use Kongono\\Database\\DatabaseValue;',
            'use Kongono\\Database\\DatabaseValueKind;',
            'use Kongono\\Database\\RowStream;',
            'use Kongono\\DatabaseMapping\\Exception\\HydrationException;',
            'use Kongono\\DatabaseMapping\\Exception\\RepositoryCleanupException;',
            'use Kongono\\DatabaseMapping\\Exception\\RepositoryExecutionException;',
            'use Kongono\\DatabaseMapping\\Exception\\RowCardinalityException;',
            'use Kongono\\DatabaseSql\\DeletePlan;',
            'use Kongono\\DatabaseSql\\InsertPlan;',
            'use Kongono\\DatabaseSql\\SelectPlan;',
            'use Kongono\\DatabaseSql\\SqlAssignment;',
            'use Kongono\\DatabaseSql\\SqlColumn;',
            'use Kongono\\DatabaseSql\\SqlComparisonOperator;',
            'use Kongono\\DatabaseSql\\SqlComparisonPredicate;',
            'use Kongono\\DatabaseSql\\SqlDialectCompiler;',
            'use Kongono\\DatabaseSql\\SqlIdentifier;',
            'use Kongono\\DatabaseSql\\SqlMutationScope;',
            'use Kongono\\DatabaseSql\\SqlProjection;',
            'use Kongono\\DatabaseSql\\SqlQualifiedName;',
            'use Kongono\\DatabaseSql\\UpdatePlan;',
            'use Throwable;',
        ];
        foreach ($mappings as $mapping) {
            $lines[] = '';
            array_push($lines, ...$this->mapper($mapping));
            $lines[] = '';
            array_push($lines, ...$this->repository($mapping));
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string, mixed> $mapping
     * @return list<string>
     */
    private function mapper(array $mapping): array
    {
        /** @var string $mapper */
        $mapper = $mapping['mapper'];
        /** @var string $record */
        $record = $mapping['record'];
        /** @var list<array<string, mixed>> $fields */
        $fields = $mapping['fields'];
        /** @var int $identityOrdinal */
        $identityOrdinal = $mapping['identity_ordinal'];
        $lines = [
            '/** Maps exact neutral rows and typed record accessors without runtime reflection. */',
            'final class ' . $mapper,
            '{',
            '    public function hydrate(DatabaseRow $row): \\' . $record,
            '    {',
            '        $columns = $row->columns();',
            '        $values = $row->values();',
            sprintf('        if (%d !== count($columns) || %d !== count($values)) {', count($fields), count($fields)),
            sprintf('            throw HydrationException::shape(0, %d);', count($fields)),
            '        }',
        ];
        foreach ($fields as $ordinal => $field) {
            $alias = $this->alias($ordinal);
            $lines[] = sprintf('        if (%d !== $columns[%d]->ordinal() || %s !== $columns[%d]->label()) {', $ordinal, $ordinal, $this->literal($alias), $ordinal);
            $lines[] = sprintf('            throw HydrationException::shape(%d, %d);', $ordinal, count($fields));
            $lines[] = '        }';
            array_push($lines, ...$this->hydrateValue($field, $ordinal));
        }
        $lines[] = '';
        $lines[] = '        return new \\' . $record . '(';
        foreach ($fields as $ordinal => $field) {
            /** @var string $parameter */
            $parameter = $field['constructor_parameter'];
            $lines[] = sprintf('            %s: $field%d,', $parameter, $ordinal);
        }
        $lines[] = '        );';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    /** @return array{insert: list<SqlAssignment>, update: list<SqlAssignment>, identity: DatabaseValue} */';
        $lines[] = '    public function extract(\\' . $record . ' $record): array';
        $lines[] = '    {';
        foreach ($fields as $ordinal => $field) {
            /** @var string $accessor */
            $accessor = $field['accessor'];
            $lines[] = sprintf('        $field%d = $record->%s();', $ordinal, $accessor);
            array_push($lines, ...$this->extractValue($field, $ordinal));
        }
        $lines[] = '';
        $lines[] = '        return [';
        $lines[] = "            'insert' => [";
        foreach ($fields as $ordinal => $field) {
            /** @var string $column */
            $column = $field['column'];
            $lines[] = sprintf('                new SqlAssignment(new SqlIdentifier(%s), $value%d),', $this->literal($column), $ordinal);
        }
        $lines[] = '            ],';
        $lines[] = "            'update' => [";
        foreach ($fields as $ordinal => $field) {
            if ($ordinal === $identityOrdinal) {
                continue;
            }
            /** @var string $column */
            $column = $field['column'];
            $lines[] = sprintf('                new SqlAssignment(new SqlIdentifier(%s), $value%d),', $this->literal($column), $ordinal);
        }
        $lines[] = '            ],';
        $lines[] = sprintf('            \'identity\' => $value%d,', $identityOrdinal);
        $lines[] = '        ];';
        $lines[] = '    }';
        $lines[] = '}';

        return $lines;
    }

    /**
     * @param array<string, mixed> $mapping
     * @return list<string>
     */
    private function repository(array $mapping): array
    {
        /** @var string $repository */
        $repository = $mapping['repository'];
        /** @var string $mapper */
        $mapper = $mapping['mapper'];
        /** @var string $record */
        $record = $mapping['record'];
        /** @var list<string> $table */
        $table = $mapping['table'];
        /** @var list<array<string, mixed>> $fields */
        $fields = $mapping['fields'];
        /** @var int $identityOrdinal */
        $identityOrdinal = $mapping['identity_ordinal'];
        $identityField = $fields[$identityOrdinal];
        /** @var string $identityKind */
        $identityKind = $identityField['kind'];
        $identityType = 'integer' === $identityKind ? 'int' : 'string';
        /** @var string $identityColumn */
        $identityColumn = $identityField['column'];
        $lines = [
            '/** Executes four bounded plans through caller-owned compiler and executor capabilities. */',
            'final class ' . $repository,
            '{',
            '    private readonly ' . $mapper . ' $mapper;',
            '',
            '    public function __construct(',
            '        private readonly DatabaseExecutor $executor,',
            '        private readonly SqlDialectCompiler $compiler,',
            '    ) {',
            '        $this->mapper = new ' . $mapper . '();',
            '    }',
            '',
            '    public function find(' . $identityType . ' $identity): ?\\' . $record,
            '    {',
            '        $plan = new SelectPlan(',
            '            source: ' . $this->qualifiedName($table) . ',',
            '            projections: [',
        ];
        foreach ($fields as $ordinal => $field) {
            /** @var string $column */
            $column = $field['column'];
            $lines[] = sprintf('                new SqlProjection(new SqlColumn(new SqlIdentifier(%s)), new SqlIdentifier(%s)),', $this->literal($column), $this->literal($this->alias($ordinal)));
        }
        $lines[] = '            ],';
        $lines[] = '            predicate: new SqlComparisonPredicate(';
        $lines[] = '                new SqlColumn(new SqlIdentifier(' . $this->literal($identityColumn) . ')),';
        $lines[] = '                SqlComparisonOperator::Equal,';
        $lines[] = '                ' . $this->valueFactory($identityKind, '$identity') . ',';
        $lines[] = '            ),';
        $lines[] = '            limit: 2,';
        $lines[] = '        );';
        $lines[] = '        $result = $this->executor->execute($this->compiler->compile($plan));';
        $lines[] = '        if (!$result instanceof RowStream) {';
        $lines[] = '            throw RepositoryExecutionException::invalidFindResult();';
        $lines[] = '        }';
        $lines[] = '';
        $lines[] = '        $primaryFailure = null;';
        $lines[] = '        $record = null;';
        $lines[] = '        try {';
        $lines[] = '            $first = $result->fetch();';
        $lines[] = '            if (null !== $first) {';
        $lines[] = '                $second = $result->fetch();';
        $lines[] = '                if (null !== $second) {';
        $lines[] = '                    throw RowCardinalityException::invalid(2);';
        $lines[] = '                }';
        $lines[] = '                $record = $this->mapper->hydrate($first);';
        $lines[] = '            }';
        $lines[] = '        } catch (Throwable $failure) {';
        $lines[] = '            $primaryFailure = $failure;';
        $lines[] = '        }';
        $lines[] = '        $cleanupFailures = [];';
        $lines[] = '        try {';
        $lines[] = '            $result->close();';
        $lines[] = '        } catch (Throwable $cleanupFailure) {';
        $lines[] = '            $cleanupFailures[] = $cleanupFailure;';
        $lines[] = '        }';
        $lines[] = '        if ([] !== $cleanupFailures) {';
        $lines[] = '            throw RepositoryCleanupException::forFind($primaryFailure, $cleanupFailures);';
        $lines[] = '        }';
        $lines[] = '        if (null !== $primaryFailure) {';
        $lines[] = '            throw $primaryFailure;';
        $lines[] = '        }';
        $lines[] = '';
        $lines[] = '        return $record;';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public function insert(\\' . $record . ' $record): CommandResult';
        $lines[] = '    {';
        $lines[] = '        $values = $this->mapper->extract($record);';
        $lines[] = '        $plan = new InsertPlan(' . $this->qualifiedName($table) . ', $values[\'insert\']);';
        $lines[] = '        $result = $this->executor->execute($this->compiler->compile($plan));';
        $lines[] = '        if (!$result instanceof CommandResult) {';
        $lines[] = '            throw RepositoryExecutionException::invalidInsertResult();';
        $lines[] = '        }';
        $lines[] = '';
        $lines[] = '        return $result;';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public function update(\\' . $record . ' $record): CommandResult';
        $lines[] = '    {';
        $lines[] = '        $values = $this->mapper->extract($record);';
        $lines[] = '        $plan = new UpdatePlan(';
        $lines[] = '            ' . $this->qualifiedName($table) . ',';
        $lines[] = '            $values[\'update\'],';
        $lines[] = '            SqlMutationScope::forPredicate(new SqlComparisonPredicate(';
        $lines[] = '                new SqlColumn(new SqlIdentifier(' . $this->literal($identityColumn) . ')),';
        $lines[] = '                SqlComparisonOperator::Equal,';
        $lines[] = '                $values[\'identity\'],';
        $lines[] = '            )),';
        $lines[] = '        );';
        $lines[] = '        $result = $this->executor->execute($this->compiler->compile($plan));';
        $lines[] = '        if (!$result instanceof CommandResult) {';
        $lines[] = '            throw RepositoryExecutionException::invalidUpdateResult();';
        $lines[] = '        }';
        $lines[] = '';
        $lines[] = '        return $result;';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public function delete(' . $identityType . ' $identity): CommandResult';
        $lines[] = '    {';
        $lines[] = '        $plan = new DeletePlan(';
        $lines[] = '            ' . $this->qualifiedName($table) . ',';
        $lines[] = '            SqlMutationScope::forPredicate(new SqlComparisonPredicate(';
        $lines[] = '                new SqlColumn(new SqlIdentifier(' . $this->literal($identityColumn) . ')),';
        $lines[] = '                SqlComparisonOperator::Equal,';
        $lines[] = '                ' . $this->valueFactory($identityKind, '$identity') . ',';
        $lines[] = '            )),';
        $lines[] = '        );';
        $lines[] = '        $result = $this->executor->execute($this->compiler->compile($plan));';
        $lines[] = '        if (!$result instanceof CommandResult) {';
        $lines[] = '            throw RepositoryExecutionException::invalidDeleteResult();';
        $lines[] = '        }';
        $lines[] = '';
        $lines[] = '        return $result;';
        $lines[] = '    }';
        $lines[] = '}';

        return $lines;
    }

    /**
     * @param array<string, mixed> $field
     * @return list<string>
     */
    private function hydrateValue(array $field, int $ordinal): array
    {
        /** @var string $kind */
        $kind = $field['kind'];
        /** @var bool $nullable */
        $nullable = $field['nullable'];
        $kindCase = $this->kindCase($kind);
        $lines = [sprintf('        $value%d = $values[%d];', $ordinal, $ordinal)];
        if ($nullable) {
            $lines[] = sprintf('        if (DatabaseValueKind::Null === $value%d->kind()) {', $ordinal);
            $lines[] = sprintf('            $field%d = null;', $ordinal);
            $lines[] = '        } else {';
            $lines[] = sprintf('            if (DatabaseValueKind::%s !== $value%d->kind()) {', $kindCase, $ordinal);
            $lines[] = sprintf('                throw HydrationException::kind(%d);', $ordinal);
            $lines[] = '            }';
            $lines[] = sprintf('            $raw%d = $value%d->value();', $ordinal, $ordinal);
            array_push($lines, ...$this->hydrateChecks($kind, '$raw' . $ordinal, $ordinal, '            '));
            $lines[] = sprintf('            $field%d = $raw%d;', $ordinal, $ordinal);
            $lines[] = '        }';
        } else {
            $lines[] = sprintf('        if (DatabaseValueKind::%s !== $value%d->kind()) {', $kindCase, $ordinal);
            $lines[] = sprintf('            throw HydrationException::kind(%d);', $ordinal);
            $lines[] = '        }';
            $lines[] = sprintf('        $raw%d = $value%d->value();', $ordinal, $ordinal);
            array_push($lines, ...$this->hydrateChecks($kind, '$raw' . $ordinal, $ordinal, '        '));
            $lines[] = sprintf('        $field%d = $raw%d;', $ordinal, $ordinal);
        }

        return $lines;
    }

    /**
     * @param array<string, mixed> $field
     * @return list<string>
     */
    private function extractValue(array $field, int $ordinal): array
    {
        /** @var string $kind */
        $kind = $field['kind'];
        /** @var bool $nullable */
        $nullable = $field['nullable'];
        if ($nullable) {
            $lines = [
                sprintf('        if (null === $field%d) {', $ordinal),
                sprintf('            $value%d = DatabaseValue::null();', $ordinal),
                '        } else {',
            ];
            if ('float' === $kind) {
                $lines[] = sprintf('            if (!is_finite($field%d)) {', $ordinal);
                $lines[] = sprintf('                throw HydrationException::value(%d);', $ordinal);
                $lines[] = '            }';
            }
            $lines[] = sprintf('            $value%d = %s;', $ordinal, $this->valueFactory($kind, '$field' . $ordinal));
            $lines[] = '        }';

            return $lines;
        }

        $lines = [];
        if ('float' === $kind) {
            $lines[] = sprintf('        if (!is_finite($field%d)) {', $ordinal);
            $lines[] = sprintf('            throw HydrationException::value(%d);', $ordinal);
            $lines[] = '        }';
        }
        $lines[] = sprintf('        $value%d = %s;', $ordinal, $this->valueFactory($kind, '$field' . $ordinal));

        return $lines;
    }

    /** @return list<string> */
    private function hydrateChecks(string $kind, string $variable, int $ordinal, string $indent): array
    {
        $function = match ($kind) {
            'boolean' => 'is_bool',
            'integer' => 'is_int',
            'float' => 'is_float',
            'text', 'binary' => 'is_string',
            default => 'is_null',
        };
        $lines = [
            sprintf('%sif (!%s(%s)) {', $indent, $function, $variable),
            sprintf('%s    throw HydrationException::value(%d);', $indent, $ordinal),
            $indent . '}',
        ];
        if ('float' === $kind) {
            $lines[] = sprintf('%sif (!is_finite(%s)) {', $indent, $variable);
            $lines[] = sprintf('%s    throw HydrationException::value(%d);', $indent, $ordinal);
            $lines[] = $indent . '}';
        }

        return $lines;
    }

    private function valueFactory(string $kind, string $variable): string
    {
        return match ($kind) {
            'boolean' => 'DatabaseValue::boolean(' . $variable . ')',
            'integer' => 'DatabaseValue::integer(' . $variable . ')',
            'float' => 'DatabaseValue::float(' . $variable . ')',
            'text' => 'DatabaseValue::text(' . $variable . ')',
            'binary' => 'DatabaseValue::binary(' . $variable . ')',
            default => 'DatabaseValue::null()',
        };
    }

    private function kindCase(string $kind): string
    {
        return match ($kind) {
            'boolean' => 'Boolean',
            'integer' => 'Integer',
            'float' => 'Float',
            'text' => 'Text',
            'binary' => 'Binary',
            default => 'Null',
        };
    }

    /** @param list<string> $segments */
    private function qualifiedName(array $segments): string
    {
        $values = array_map(fn(string $segment): string => 'new SqlIdentifier(' . $this->literal($segment) . ')', $segments);

        return 'new SqlQualifiedName([' . implode(', ', $values) . '])';
    }

    private function alias(int $ordinal): string
    {
        return sprintf('kgn_f%04d', $ordinal);
    }

    private function literal(string $value): string
    {
        return var_export($value, true);
    }
}
