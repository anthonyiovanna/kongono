<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Context;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;

/** Defines an ordered closed structural schema for one diagnostic context. */
#[Api(ApiStatus::Public)]
final readonly class DiagnosticContextSchema
{
    private string $operation;
    private string $violatedInvariant;

    /** @var list<DiagnosticFieldSchema> */
    private array $fields;

    /** @param array<array-key, mixed> $fields */
    public function __construct(
        string $operation,
        string $violatedInvariant,
        array $fields = [],
    ) {
        if ('' === $operation || 1 !== preg_match('//u', $operation) || strlen($operation) > 160) {
            throw new InvalidDiagnosticValueException(
                DiagnosticsDiagnostic::InvalidOperation,
                'Diagnostic context operation must be non-empty valid UTF-8 and contain at most 160 bytes.',
            );
        }

        if (
            '' === $violatedInvariant
            || 1 !== preg_match('//u', $violatedInvariant)
            || strlen($violatedInvariant) > 256
        ) {
            throw new InvalidDiagnosticValueException(
                DiagnosticsDiagnostic::InvalidInvariant,
                'Diagnostic context invariant must be non-empty valid UTF-8 and contain at most 256 bytes.',
            );
        }

        if (!array_is_list($fields)) {
            throw new InvalidDiagnosticValueException(
                DiagnosticsDiagnostic::FieldSchemasNotList,
                'Diagnostic field schemas must be supplied as a zero-based consecutive list.',
            );
        }

        $detachedFields = [];

        foreach ($fields as $field) {
            $detachedFields[] = $field;
        }

        if (count($detachedFields) > 16) {
            throw new InvalidDiagnosticValueException(
                DiagnosticsDiagnostic::TooManyFieldSchemas,
                sprintf('Diagnostic context schema may contain at most 16 fields; received %d.', count($detachedFields)),
            );
        }

        $validatedFields = [];
        $keys = [];

        foreach ($detachedFields as $position => $field) {
            if (!$field instanceof DiagnosticFieldSchema) {
                throw new InvalidDiagnosticValueException(
                    DiagnosticsDiagnostic::InvalidFieldSchemaEntry,
                    sprintf('Diagnostic field schema at position %d must be a DiagnosticFieldSchema.', $position),
                );
            }

            if (array_key_exists($field->key(), $keys)) {
                throw new InvalidDiagnosticValueException(
                    DiagnosticsDiagnostic::DuplicateFieldSchemaKey,
                    sprintf('Diagnostic context schema contains duplicate field key "%s".', $field->key()),
                );
            }

            $keys[$field->key()] = true;
            $validatedFields[] = $field;
        }

        $this->operation = $operation;
        $this->violatedInvariant = $violatedInvariant;
        $this->fields = $validatedFields;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function violatedInvariant(): string
    {
        return $this->violatedInvariant;
    }

    /** @return list<DiagnosticFieldSchema> */
    public function fields(): array
    {
        return $this->fields;
    }
}
