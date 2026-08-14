<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Context;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;

/**
 * Stores a detached schema-ordered snapshot of approved diagnostic field values.
 *
 * Construction validates structural compliance only. Schema owners remain
 * responsible for diagnostic meaning and exclusion of forbidden material.
 */
#[Api(ApiStatus::Public)]
final readonly class DiagnosticContext
{
    private string $operation;
    private string $violatedInvariant;

    /** @var list<DiagnosticField> */
    private array $fields;

    /** @param array<array-key, mixed> $values */
    public function __construct(
        DiagnosticContextSchema $schema,
        array $values = [],
    ) {
        $operation = $schema->operation();
        $violatedInvariant = $schema->violatedInvariant();
        $fieldSchemas = $schema->fields();
        $detachedValues = [];

        foreach ($values as $key => $value) {
            $detachedValues[$key] = $value;
        }

        $schemasByKey = [];

        foreach ($fieldSchemas as $fieldSchema) {
            $schemasByKey[$fieldSchema->key()] = $fieldSchema;
        }

        $smallestIntegerKey = null;
        $smallestUnknownStringKey = null;

        foreach ($detachedValues as $key => $_value) {
            if (is_int($key)) {
                if (null === $smallestIntegerKey || $key < $smallestIntegerKey) {
                    $smallestIntegerKey = $key;
                }

                continue;
            }

            if (!array_key_exists($key, $schemasByKey)) {
                if (null === $smallestUnknownStringKey || strcmp($key, $smallestUnknownStringKey) < 0) {
                    $smallestUnknownStringKey = $key;
                }
            }
        }

        if (null !== $smallestIntegerKey) {
            throw new InvalidDiagnosticValueException(
                DiagnosticsDiagnostic::InvalidContextValueKey,
                sprintf(
                    'Diagnostic context value key must be a schema-approved string; received integer key %d.',
                    $smallestIntegerKey,
                ),
            );
        }

        if (null !== $smallestUnknownStringKey) {
            throw new InvalidDiagnosticValueException(
                DiagnosticsDiagnostic::InvalidContextValueKey,
                sprintf(
                    'Diagnostic context value key "%s" is not present in the closed schema.',
                    $smallestUnknownStringKey,
                ),
            );
        }

        $fields = [];

        foreach ($fieldSchemas as $fieldSchema) {
            if (!array_key_exists($fieldSchema->key(), $detachedValues)) {
                if ($fieldSchema->isRequired()) {
                    throw new InvalidDiagnosticValueException(
                        DiagnosticsDiagnostic::MissingRequiredContextValue,
                        sprintf('Diagnostic context is missing required field value "%s".', $fieldSchema->key()),
                    );
                }

                continue;
            }

            $value = $detachedValues[$fieldSchema->key()];

            if (!$this->valueMatchesKind($fieldSchema->kind(), $value)) {
                throw new InvalidDiagnosticValueException(
                    DiagnosticsDiagnostic::ContextValueKindMismatch,
                    sprintf(
                        'Diagnostic field "%s" requires %s; received %s.',
                        $fieldSchema->key(),
                        $fieldSchema->kind()->value,
                        get_debug_type($value),
                    ),
                );
            }

            /** @var string|int|bool $value */
            $fields[] = new DiagnosticField($fieldSchema, $value);
        }

        $this->operation = $operation;
        $this->violatedInvariant = $violatedInvariant;
        $this->fields = $fields;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function violatedInvariant(): string
    {
        return $this->violatedInvariant;
    }

    /** @return list<DiagnosticField> */
    public function fields(): array
    {
        return $this->fields;
    }

    private function valueMatchesKind(DiagnosticValueKind $kind, mixed $value): bool
    {
        return match ($kind) {
            DiagnosticValueKind::Text => is_string($value),
            DiagnosticValueKind::Integer => is_int($value),
            DiagnosticValueKind::Boolean => is_bool($value),
        };
    }
}
