<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Model\ConstructionKind;
use Kongono\PersistenceBuild\Model\EntityDefinition;
use Kongono\PersistenceBuild\Model\FieldDefinition;
use Kongono\PersistenceBuild\Model\FieldValueKind;
use Kongono\PersistenceBuild\Model\PersistenceModel;

/** Emits canonical ordinary PHP adapters from one detached persistence model. */
#[Api(ApiStatus::Internal)]
final class GeneratedPersistenceStateEmitter
{
    public function emit(
        PersistenceModel $model,
        string $persistenceArtifactSha256,
        string $persistenceModelSha256,
        string $persistenceBuilderImplementationSha256,
        string $persistenceGeneratorImplementationSha256,
    ): string {
        $namespace = $model->generatedNamespace() . '\\Artifact_' . substr($persistenceArtifactSha256, 0, 32);
        $classMap = [];
        foreach ($model->entities() as $ordinal => $entity) {
            $classMap[] = [
                'entity' => $entity->entity(),
                'state_adapter' => $namespace . '\\' . $this->className($ordinal),
            ];
        }
        $metadata = json_encode([
            'schema' => 'kongono.generated-persistence-state',
            'version' => 1,
            'persistence_artifact_sha256' => $persistenceArtifactSha256,
            'persistence_model_sha256' => $persistenceModelSha256,
            'persistence_builder_implementation_sha256' => $persistenceBuilderImplementationSha256,
            'persistence_generator_implementation_sha256' => $persistenceGeneratorImplementationSha256,
            'generated_namespace' => $namespace,
            'class_map' => $classMap,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '// ' . $metadata,
            '',
            'namespace ' . $namespace . ';',
            '',
            'use Kongono\\Persistence\\ChangeKind;',
            'use Kongono\\Persistence\\ChangeSet;',
            'use Kongono\\Persistence\\ConcurrencyToken;',
            'use Kongono\\Persistence\\EntityIdentity;',
            'use Kongono\\Persistence\\PersistenceChange;',
            'use Kongono\\PersistenceBuild\\Exception\\InvalidGeneratedPersistenceStateException;',
        ];
        foreach ($model->entities() as $ordinal => $entity) {
            $lines[] = '';
            array_push($lines, ...$this->adapter($entity, $this->className($ordinal)));
        }

        return implode("\n", $lines) . "\n";
    }

    /** @return list<string> */
    private function adapter(EntityDefinition $entity, string $class): array
    {
        $shape = $this->shape($entity);
        $recordDoc = 'array<string, bool|int|float|string|null>';
        $entityType = '\\' . $entity->entity();
        $lines = [
            '/** Provides detached state evidence through authenticated application construction and accessors. */',
            'final class ' . $class,
            '{',
            '    private function __construct() {}',
            '',
            '    /**',
            '     * @param ' . $recordDoc . ' $record',
            '     */',
            '    public static function hydrate(array $record): ' . $entityType,
            '    {',
        ];
        array_push($lines, ...$this->shapeValidation($entity, '$record', '        '));
        foreach ($entity->fields() as $ordinal => $field) {
            $variable = '$field' . $ordinal;
            $lines[] = sprintf('        %s = $record[%s];', $variable, $this->literal($field->recordField()));
            array_push($lines, ...$this->valueValidation($field, $variable, '        '));
        }
        $lines[] = '';
        $construction = $entity->construction();
        $prefix = ConstructionKind::Constructor === $construction->kind()
            ? 'new ' . $entityType
            : $entityType . '::' . $construction->method();
        $lines[] = '        return ' . $prefix . '(';
        foreach ($entity->fields() as $ordinal => $field) {
            $lines[] = sprintf('            %s: $field%d,', $field->factoryParameter(), $ordinal);
        }
        $lines[] = '        );';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    /** @return ' . $shape . ' */';
        $lines[] = '    public static function extract(' . $entityType . ' $entity): array';
        $lines[] = '    {';
        foreach ($entity->fields() as $ordinal => $field) {
            $variable = '$field' . $ordinal;
            $lines[] = sprintf('        %s = $entity->%s();', $variable, $field->accessor());
            array_push($lines, ...$this->valueValidation($field, $variable, '        ', null, true));
        }
        $lines[] = '';
        $lines[] = '        return [';
        foreach ($entity->fields() as $ordinal => $field) {
            $lines[] = sprintf('            %s => $field%d,', $this->literal($field->recordField()), $ordinal);
        }
        $lines[] = '        ];';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public static function identity(' . $entityType . ' $entity): EntityIdentity';
        $lines[] = '    {';
        $identity = $this->field($entity, $entity->identity());
        $lines[] = sprintf('        $value = $entity->%s();', $identity->accessor());
        array_push($lines, ...$this->valueValidation($identity, '$value', '        ', false, true));
        array_push($lines, ...$this->encoding('$value', '$encoded', $identity->valueKind(), '        '));
        $lines[] = '        if (strlen($encoded) > 512) {';
        $lines[] = '            throw InvalidGeneratedPersistenceStateException::value();';
        $lines[] = '        }';
        $lines[] = '';
        $lines[] = '        return new EntityIdentity(' . $this->literal($entity->entity()) . ', $encoded);';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    /** @return ' . $shape . ' */';
        $lines[] = '    public static function snapshot(' . $entityType . ' $entity): array';
        $lines[] = '    {';
        $lines[] = '        return self::extract($entity);';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    /** @param ' . $recordDoc . ' $snapshot */';
        $lines[] = '    public static function changes(' . $entityType . ' $entity, array $snapshot): ChangeSet';
        $lines[] = '    {';
        array_push($lines, ...$this->shapeValidation($entity, '$snapshot', '        '));
        foreach ($entity->fields() as $ordinal => $field) {
            $variable = '$snapshotField' . $ordinal;
            $lines[] = sprintf('        %s = $snapshot[%s];', $variable, $this->literal($field->recordField()));
            array_push($lines, ...$this->valueValidation($field, $variable, '        '));
        }
        $lines[] = '        $current = self::extract($entity);';
        $lines[] = '        $changedFields = [];';
        foreach ($entity->fields() as $field) {
            $recordField = $this->literal($field->recordField());
            $lines[] = sprintf('        if ($current[%s] !== $snapshot[%s]) {', $recordField, $recordField);
            $lines[] = '            $changedFields[] = ' . $this->literal($field->field()) . ';';
            $lines[] = '        }';
        }
        $lines[] = '        if ([] === $changedFields) {';
        $lines[] = '            return new ChangeSet([]);';
        $lines[] = '        }';
        $lines[] = '';
        array_push($lines, ...$this->concurrency($entity, '$snapshot', '$expectedConcurrencyToken', '        ', false));
        $lines[] = '        return new ChangeSet([';
        $lines[] = '            new PersistenceChange($entity, ChangeKind::Update, self::identity($entity), $changedFields, $expectedConcurrencyToken),';
        $lines[] = '        ]);';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public static function insertion(' . $entityType . ' $entity): ChangeSet';
        $lines[] = '    {';
        $lines[] = '        self::extract($entity);';
        $lines[] = '';
        $lines[] = '        return new ChangeSet([';
        $lines[] = '            new PersistenceChange($entity, ChangeKind::Insert, null, [';
        foreach ($entity->fields() as $field) {
            $lines[] = '                ' . $this->literal($field->field()) . ',';
        }
        $lines[] = '            ], null),';
        $lines[] = '        ]);';
        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public static function deletion(' . $entityType . ' $entity): ChangeSet';
        $lines[] = '    {';
        $lines[] = '        $current = self::extract($entity);';
        array_push($lines, ...$this->concurrency($entity, '$current', '$expectedConcurrencyToken', '        ', true));
        $lines[] = '';
        $lines[] = '        return new ChangeSet([';
        $lines[] = '            new PersistenceChange($entity, ChangeKind::Delete, self::identity($entity), [], $expectedConcurrencyToken),';
        $lines[] = '        ]);';
        $lines[] = '    }';
        $lines[] = '}';

        return $lines;
    }

    /** @return list<string> */
    private function shapeValidation(EntityDefinition $entity, string $variable, string $indent): array
    {
        $conditions = [sprintf('%d !== count(%s)', count($entity->fields()), $variable)];
        foreach ($entity->fields() as $field) {
            $conditions[] = sprintf('!array_key_exists(%s, %s)', $this->literal($field->recordField()), $variable);
        }
        $lines = [$indent . 'if ('];
        foreach ($conditions as $ordinal => $condition) {
            $prefix = 0 === $ordinal ? '    ' : '    || ';
            $lines[] = $indent . $prefix . $condition;
        }
        $lines[] = $indent . ') {';
        $lines[] = $indent . '    throw InvalidGeneratedPersistenceStateException::value();';
        $lines[] = $indent . '}';

        return $lines;
    }

    /** @return list<string> */
    private function valueValidation(
        FieldDefinition $field,
        string $variable,
        string $indent,
        ?bool $nullable = null,
        bool $nativeTypeGuaranteed = false,
    ): array {
        $nullable ??= $field->nullable();
        $predicate = match (true) {
            $nativeTypeGuaranteed && FieldValueKind::Boolean === $field->valueKind() => null,
            $nativeTypeGuaranteed && FieldValueKind::Integer === $field->valueKind() => null,
            $nativeTypeGuaranteed && FieldValueKind::Float === $field->valueKind() => 'is_finite(' . $variable . ')',
            $nativeTypeGuaranteed && FieldValueKind::Text === $field->valueKind() => '1 === preg_match(\'//u\', ' . $variable . ')',
            $nativeTypeGuaranteed && FieldValueKind::Binary === $field->valueKind() => null,
            FieldValueKind::Boolean === $field->valueKind() => 'is_bool(' . $variable . ')',
            FieldValueKind::Integer === $field->valueKind() => 'is_int(' . $variable . ')',
            FieldValueKind::Float === $field->valueKind() => 'is_float(' . $variable . ') && is_finite(' . $variable . ')',
            FieldValueKind::Text === $field->valueKind() => 'is_string(' . $variable . ') && 1 === preg_match(\'//u\', ' . $variable . ')',
            FieldValueKind::Binary === $field->valueKind() => 'is_string(' . $variable . ')',
        };
        if (null === $predicate) {
            return [];
        }
        $condition = $nullable
            ? 'null !== ' . $variable . ' && !(' . $predicate . ')'
            : '!(' . $predicate . ')';

        return [
            $indent . 'if (' . $condition . ') {',
            $indent . '    throw InvalidGeneratedPersistenceStateException::value();',
            $indent . '}',
        ];
    }

    /** @return list<string> */
    private function encoding(string $value, string $encoded, FieldValueKind $kind, string $indent): array
    {
        $expression = match ($kind) {
            FieldValueKind::Boolean => "'b:' . (" . $value . " ? '1' : '0')",
            FieldValueKind::Integer => "'i:' . (string) " . $value,
            FieldValueKind::Float => "'f:' . bin2hex(pack('E', " . $value . '))',
            FieldValueKind::Text => "'t:' . base64_encode(" . $value . ')',
            FieldValueKind::Binary => "'x:' . base64_encode(" . $value . ')',
        };

        return [$indent . $encoded . ' = ' . $expression . ';'];
    }

    /** @return list<string> */
    private function concurrency(EntityDefinition $entity, string $record, string $token, string $indent, bool $nativeTypeGuaranteed): array
    {
        if (null === $entity->concurrency()) {
            return [$indent . $token . ' = null;'];
        }
        $field = $this->field($entity, $entity->concurrency());
        $value = '$concurrencyValue';
        $encoded = '$concurrencyEncoded';
        $lines = [sprintf('%s%s = %s[%s];', $indent, $value, $record, $this->literal($field->recordField()))];
        array_push($lines, ...$this->valueValidation($field, $value, $indent, false, $nativeTypeGuaranteed));
        array_push($lines, ...$this->encoding($value, $encoded, $field->valueKind(), $indent));
        $lines[] = $indent . 'if (strlen(' . $encoded . ') > 512) {';
        $lines[] = $indent . '    throw InvalidGeneratedPersistenceStateException::value();';
        $lines[] = $indent . '}';
        $lines[] = $indent . $token . " = new ConcurrencyToken('kongono.scalar-v1', " . $encoded . ');';

        return $lines;
    }

    private function field(EntityDefinition $entity, string $name): FieldDefinition
    {
        $field = $entity->field($name);
        if (null === $field) {
            throw new \LogicException('Validated persistence entity lost a required field.');
        }

        return $field;
    }

    private function shape(EntityDefinition $entity): string
    {
        $fields = [];
        foreach ($entity->fields() as $field) {
            $type = match ($field->valueKind()) {
                FieldValueKind::Boolean => 'bool',
                FieldValueKind::Integer => 'int',
                FieldValueKind::Float => 'float',
                FieldValueKind::Text, FieldValueKind::Binary => 'string',
            };
            $fields[] = $field->recordField() . ':' . ($field->nullable() ? $type . '|null' : $type);
        }

        return 'array{' . implode(', ', $fields) . '}';
    }

    private function className(int $zeroBasedOrdinal): string
    {
        return sprintf('EntityState_%04d', $zeroBasedOrdinal + 1);
    }

    private function literal(string $value): string
    {
        return var_export($value, true);
    }
}
