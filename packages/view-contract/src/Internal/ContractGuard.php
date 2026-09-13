<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\ContractVersion;
use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Schema\PresentationFieldSchema;
use Kongono\ViewContract\Value\BooleanValue;
use Kongono\ViewContract\Value\IntegerValue;
use Kongono\ViewContract\Value\ListValue;
use Kongono\ViewContract\Value\NullValue;
use Kongono\ViewContract\Value\PresentationValue;
use Kongono\ViewContract\Value\RecordValue;
use Kongono\ViewContract\Value\StringValue;

/** Centralizes exact local validation without exposing a generic validator. */
#[Api(ApiStatus::Internal)]
final class ContractGuard
{
    public static function identity(string $value, string $violation): string
    {
        if (strlen($value) < 1 || strlen($value) > 128 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value)) {
            throw InvalidViewContractException::invalidIdentity($violation);
        }

        return $value;
    }

    public static function compiledIdentity(string $value): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidViewContractException::invalidIdentity('compiled_view_identity_grammar');
        }

        return $value;
    }

    public static function fieldName(string $name): string
    {
        if (strlen($name) < 1 || strlen($name) > 64 || 1 !== preg_match('/\A[a-z][a-z0-9_]*\z/D', $name)) {
            throw InvalidViewContractException::invalidPresentationValue('field_name_grammar');
        }

        return $name;
    }

    public static function presentationText(string $value, int $maximum): string
    {
        if (strlen($value) > $maximum) {
            throw InvalidViewContractException::contractBoundExceeded('presentation_text_bytes', $maximum);
        }
        if (!self::validUtf8($value) || str_starts_with($value, "\xEF\xBB\xBF") || 1 === preg_match('/[\x{0000}-\x{0008}\x{000B}-\x{001F}\x{007F}-\x{009F}]/u', $value)) {
            throw InvalidViewContractException::invalidPresentationValue('presentation_text_grammar');
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<PresentationValue>
     */
    public static function valueList(array $values, int $maximum): array
    {
        if (!array_is_list($values)) {
            throw InvalidViewContractException::invalidPresentationValue('values_not_list', count($values));
        }
        if (count($values) > $maximum) {
            throw InvalidViewContractException::contractBoundExceeded('list_entry_count', $maximum, count($values));
        }
        $validated = [];
        foreach ($values as $position => $value) {
            if (!$value instanceof PresentationValue || !self::ownedValue($value)) {
                throw InvalidViewContractException::invalidPresentationValue('invalid_value_entry', count($values), $position);
            }
            $validated[] = $value;
        }

        return $validated;
    }

    /**
     * @param array<array-key, mixed> $fields
     * @return array<string, PresentationValue>
     */
    public static function valueRecord(array $fields, int $maximum): array
    {
        if (count($fields) > $maximum) {
            throw InvalidViewContractException::contractBoundExceeded('record_field_count', $maximum, count($fields));
        }
        $validated = [];
        foreach ($fields as $name => $value) {
            if (!is_string($name)) {
                throw InvalidViewContractException::invalidPresentationValue('field_name_grammar');
            }
            self::fieldName($name);
            if (!$value instanceof PresentationValue || !self::ownedValue($value)) {
                throw InvalidViewContractException::invalidPresentationValue('invalid_field_entry');
            }
            $validated[$name] = $value;
        }
        ksort($validated, SORT_STRING);

        return $validated;
    }

    public static function valueTree(int $depth, int $nodeCount): void
    {
        if ($depth > 16) {
            throw InvalidViewContractException::contractBoundExceeded('value_depth', 16, depth: $depth);
        }
        if ($nodeCount > 4096) {
            throw InvalidViewContractException::contractBoundExceeded('value_node_count', 4096, $nodeCount);
        }
    }

    /**
     * @param array<array-key, mixed> $fields
     * @return list<PresentationFieldSchema>
     */
    public static function schemaFields(array $fields): array
    {
        if (!array_is_list($fields)) {
            throw InvalidViewContractException::invalidPresentationSchema('fields_not_list', count($fields));
        }
        if (count($fields) > 128) {
            throw InvalidViewContractException::invalidPresentationSchema('field_count', count($fields), limit: 128);
        }
        $byName = [];
        foreach ($fields as $position => $field) {
            if (!$field instanceof PresentationFieldSchema) {
                throw InvalidViewContractException::invalidPresentationSchema('invalid_field_schema', count($fields), $position);
            }
            if (isset($byName[$field->name()])) {
                throw InvalidViewContractException::invalidPresentationSchema('duplicate_field', count($fields), $position);
            }
            $byName[$field->name()] = $field;
        }
        ksort($byName, SORT_STRING);

        return array_values($byName);
    }

    public static function schemaTree(int $depth, int $nodeCount): void
    {
        if ($depth > 16) {
            throw InvalidViewContractException::invalidPresentationSchema('invalid_schema_shape', depth: $depth);
        }
        if ($nodeCount > 4096) {
            throw InvalidViewContractException::invalidPresentationSchema('invalid_schema_shape', entryCount: $nodeCount, limit: 4096);
        }
    }

    public static function renderedOutput(string $bytes, int $maximum): string
    {
        if (strlen($bytes) > $maximum) {
            throw InvalidViewContractException::contractBoundExceeded('rendered_output_bytes', $maximum, strlen($bytes));
        }
        if (!self::validUtf8($bytes) || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\0") || str_contains($bytes, "\r")) {
            throw InvalidViewContractException::invalidRenderResult('rendered_output_grammar');
        }

        return $bytes;
    }

    private static function validUtf8(string $value): bool
    {
        return 1 === preg_match('//u', $value);
    }

    public static function ownedValue(PresentationValue $value): bool
    {
        return in_array($value::class, [StringValue::class, IntegerValue::class, BooleanValue::class, NullValue::class, ListValue::class, RecordValue::class], true);
    }

    public static function sameContractVersion(
        ContractVersion $left,
        ContractVersion $right,
    ): bool {
        return match ($left) {
            ContractVersion::Version1 => match ($right) {
                ContractVersion::Version1 => true,
            },
        };
    }
}
