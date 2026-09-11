<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidStrictJsonSchemaException;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use stdClass;

/** Validates definitions and decoded values against the closed strict subset. */
#[Api(ApiStatus::Internal)]
final class StrictJsonSchemaValidator
{
    public const int MAXIMUM_LOGICAL_DEPTH = 8;
    public const int MAXIMUM_NODES = 128;
    public const int MAXIMUM_PROPERTIES = 32;
    public const int MAXIMUM_REQUIRED = 32;
    public const int MAXIMUM_ENUM_ENTRIES = 32;
    public const int MAXIMUM_DESCRIPTION_BYTES = 1024;
    public const int MAXIMUM_PROPERTY_NAME_BYTES = 64;

    /** @return array<string, mixed> */
    public function validateDefinition(stdClass $schema): array
    {
        $nodes = 0;
        $validated = $this->schemaNode($schema, 1, $nodes);
        if ('object' !== $validated['type']) {
            throw InvalidStrictJsonSchemaException::violation('root_object', 'type');
        }

        return $validated;
    }

    public function validateValue(mixed $value, StrictJsonSchema $schema): string
    {
        $nodes = 0;
        $this->valueNode($value, $schema->structure(), 1, $nodes);
        try {
            return json_encode(StrictJsonSchemaCodec::canonicalize($value), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            throw InvalidStrictJsonSchemaException::violation('value_type', 'value');
        }
    }

    /** @return array<string, mixed> */
    private function schemaNode(mixed $node, int $depth, int &$nodes): array
    {
        if (!$node instanceof stdClass) {
            throw InvalidStrictJsonSchemaException::violation('node_object', 'schema');
        }
        if ($depth > self::MAXIMUM_LOGICAL_DEPTH) {
            throw InvalidStrictJsonSchemaException::violation('logical_depth', 'schema', self::MAXIMUM_LOGICAL_DEPTH);
        }
        if (++$nodes > self::MAXIMUM_NODES) {
            throw InvalidStrictJsonSchemaException::violation('node_count', 'schema', self::MAXIMUM_NODES, $nodes);
        }
        $members = self::objectMembers($node);
        if (!isset($members['type']) || !is_string($members['type'])) {
            throw InvalidStrictJsonSchemaException::violation('type_required', 'type');
        }
        $type = $members['type'];
        if (!in_array($type, ['object', 'array', 'string', 'integer', 'number', 'boolean', 'null'], true)) {
            throw InvalidStrictJsonSchemaException::violation('type_unsupported', 'type');
        }
        $allowed = match ($type) {
            'object' => ['additionalProperties', 'description', 'properties', 'required', 'type'],
            'array' => ['description', 'items', 'type'],
            default => ['description', 'enum', 'type'],
        };
        $keys = array_keys($members);
        sort($keys, SORT_STRING);
        if ($keys !== $allowed && array_diff($keys, $allowed) !== []) {
            throw InvalidStrictJsonSchemaException::violation('object_members', 'schema');
        }
        if (isset($members['description']) && (!is_string($members['description']) || '' === $members['description'] || strlen($members['description']) > self::MAXIMUM_DESCRIPTION_BYTES)) {
            throw InvalidStrictJsonSchemaException::violation('description', 'description', self::MAXIMUM_DESCRIPTION_BYTES);
        }

        $result = ['type' => $type];
        if (isset($members['description'])) {
            $result['description'] = $members['description'];
        }
        if ('object' === $type) {
            return $this->objectSchema($members, $result, $depth, $nodes);
        }
        if ('array' === $type) {
            if (!array_key_exists('items', $members)) {
                throw InvalidStrictJsonSchemaException::violation('items', 'items');
            }
            $result['items'] = $this->schemaNode($members['items'], $depth + 1, $nodes);

            return $result;
        }
        if (array_key_exists('enum', $members)) {
            $result['enum'] = $this->scalarEnum($members['enum'], $type);
        }

        return $result;
    }

    /** @param array<string, mixed> $members
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function objectSchema(array $members, array $result, int $depth, int &$nodes): array
    {
        if (!isset($members['properties']) || !$members['properties'] instanceof stdClass) {
            throw InvalidStrictJsonSchemaException::violation('properties', 'properties');
        }
        $properties = self::objectMembers($members['properties']);
        if (count($properties) > self::MAXIMUM_PROPERTIES) {
            throw InvalidStrictJsonSchemaException::violation('property_count', 'properties', self::MAXIMUM_PROPERTIES, count($properties));
        }
        $validatedProperties = [];
        foreach ($properties as $name => $child) {
            if (strlen($name) > self::MAXIMUM_PROPERTY_NAME_BYTES || 1 !== preg_match('/\A[A-Za-z_][A-Za-z0-9_-]{0,63}\z/D', $name)) {
                throw InvalidStrictJsonSchemaException::violation('property_name', 'properties', self::MAXIMUM_PROPERTY_NAME_BYTES);
            }
            $validatedProperties[$name] = $this->schemaNode($child, $depth + 1, $nodes);
        }
        ksort($validatedProperties, SORT_STRING);
        if (!isset($members['required']) || !is_array($members['required']) || count($members['required']) > self::MAXIMUM_REQUIRED) {
            throw InvalidStrictJsonSchemaException::violation('required', 'required', self::MAXIMUM_REQUIRED);
        }
        $required = [];
        foreach ($members['required'] as $entry) {
            if (!is_string($entry) || isset($required[base64_encode($entry)])) {
                throw InvalidStrictJsonSchemaException::violation('required_count', 'required', self::MAXIMUM_REQUIRED, count($members['required']));
            }
            $required[base64_encode($entry)] = $entry;
        }
        $requiredNames = array_values($required);
        $requiredSorted = $requiredNames;
        sort($requiredSorted, SORT_STRING);
        $propertyNames = array_keys($validatedProperties);
        if ($requiredSorted !== $propertyNames) {
            throw InvalidStrictJsonSchemaException::violation('required_relation', 'required');
        }
        if (!array_key_exists('additionalProperties', $members) || false !== $members['additionalProperties']) {
            throw InvalidStrictJsonSchemaException::violation('additional_properties', 'additional_properties');
        }
        $result['properties'] = $validatedProperties;
        $result['required'] = $requiredNames;
        $result['additionalProperties'] = false;

        return $result;
    }

    /** @return list<string|int|float|bool|null> */
    private function scalarEnum(mixed $enum, string $type): array
    {
        if (!is_array($enum) || [] === $enum || count($enum) > self::MAXIMUM_ENUM_ENTRIES) {
            throw InvalidStrictJsonSchemaException::violation('enum_count', 'enum', self::MAXIMUM_ENUM_ENTRIES, is_array($enum) ? count($enum) : null);
        }
        $seen = [];
        $values = [];
        foreach ($enum as $value) {
            $value = $this->scalarValue($value, $type, 'enum_type', 'enum');
            $identity = get_debug_type($value) . ':' . json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (isset($seen[$identity])) {
                throw InvalidStrictJsonSchemaException::violation('enum_duplicate', 'enum');
            }
            $seen[$identity] = true;
            $values[] = $value;
        }

        return $values;
    }

    /** @param array<string, mixed> $schema */
    private function valueNode(mixed $value, array $schema, int $depth, int &$nodes): void
    {
        if ($depth > self::MAXIMUM_LOGICAL_DEPTH) {
            throw InvalidStrictJsonSchemaException::violation('value_depth', 'value', self::MAXIMUM_LOGICAL_DEPTH);
        }
        if (++$nodes > self::MAXIMUM_NODES) {
            throw InvalidStrictJsonSchemaException::violation('value_count', 'value', self::MAXIMUM_NODES, $nodes);
        }
        $type = $schema['type'] ?? null;
        if (!is_string($type)) {
            throw InvalidStrictJsonSchemaException::violation('value_type', 'value');
        }
        if ('object' === $type) {
            if (!$value instanceof stdClass) {
                throw InvalidStrictJsonSchemaException::violation('value_type', 'value');
            }
            $members = self::objectMembers($value);
            $keys = array_keys($members);
            sort($keys, SORT_STRING);
            $propertiesValue = $schema['properties'] ?? null;
            if (!is_array($propertiesValue)) {
                throw InvalidStrictJsonSchemaException::violation('value_properties', 'value');
            }
            $properties = self::stringKeyedArray($propertiesValue, 'value_properties', 'value');
            if ($keys !== array_keys($properties)) {
                throw InvalidStrictJsonSchemaException::violation('value_properties', 'value');
            }
            foreach ($properties as $name => $child) {
                if (!is_array($child)) {
                    throw InvalidStrictJsonSchemaException::violation('value_type', 'value');
                }
                $this->valueNode($members[$name], self::stringKeyedArray($child, 'value_type', 'value'), $depth + 1, $nodes);
            }

            return;
        }
        if ('array' === $type) {
            if (!is_array($value)) {
                throw InvalidStrictJsonSchemaException::violation('value_type', 'value');
            }
            $items = $schema['items'] ?? null;
            if (!is_array($items)) {
                throw InvalidStrictJsonSchemaException::violation('value_type', 'value');
            }
            $itemSchema = self::stringKeyedArray($items, 'value_type', 'value');
            foreach ($value as $entry) {
                $this->valueNode($entry, $itemSchema, $depth + 1, $nodes);
            }

            return;
        }
        $value = $this->scalarValue($value, $type, 'value_type', 'value');
        if (array_key_exists('enum', $schema)) {
            if (!is_array($schema['enum'])) {
                throw InvalidStrictJsonSchemaException::violation('value_enum', 'value');
            }
            foreach ($schema['enum'] as $candidate) {
                $candidate = $this->scalarValue($candidate, $type, 'value_enum', 'value');
                if ($candidate === $value) {
                    return;
                }
                if ('number' === $type && (is_int($candidate) || is_float($candidate)) && (is_int($value) || is_float($value)) && (float) $candidate === (float) $value) {
                    return;
                }
            }
            throw InvalidStrictJsonSchemaException::violation('value_enum', 'value');
        }
    }

    private function scalarValue(mixed $value, string $type, string $violation, string $field): string|int|float|bool|null
    {
        return match ($type) {
            'string' => is_string($value) ? $value : throw InvalidStrictJsonSchemaException::violation($violation, $field),
            'integer' => is_int($value) ? $value : throw InvalidStrictJsonSchemaException::violation($violation, $field),
            'number' => is_int($value) || is_float($value) ? $value : throw InvalidStrictJsonSchemaException::violation($violation, $field),
            'boolean' => is_bool($value) ? $value : throw InvalidStrictJsonSchemaException::violation($violation, $field),
            'null' => null === $value ? null : throw InvalidStrictJsonSchemaException::violation($violation, $field),
            default => throw InvalidStrictJsonSchemaException::violation($violation, $field),
        };
    }

    /** @return array<string, mixed> */
    private static function objectMembers(stdClass $value): array
    {
        return self::stringKeyedArray(get_object_vars($value), 'node_object', 'schema');
    }

    /** @param array<mixed, mixed> $value
     * @return array<string, mixed>
     */
    private static function stringKeyedArray(array $value, string $violation, string $field): array
    {
        $members = [];
        foreach ($value as $key => $member) {
            if (!is_string($key)) {
                throw InvalidStrictJsonSchemaException::violation($violation, $field);
            }
            $members[$key] = $member;
        }

        return $members;
    }
}
