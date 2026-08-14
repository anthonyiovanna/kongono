<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Owns fixed metadata for failures constructing diagnostic structural values.
 *
 * These failures intentionally carry no diagnostic context because constructing
 * that context is the operation that failed.
 */
#[Api(ApiStatus::Internal)]
enum DiagnosticsDiagnostic: string
{
    case InvalidFieldKey = 'KGN-DIAGNOSTICS-1001';
    case InvalidFieldLabel = 'KGN-DIAGNOSTICS-1002';
    case InvalidOperation = 'KGN-DIAGNOSTICS-1003';
    case InvalidInvariant = 'KGN-DIAGNOSTICS-1004';
    case FieldSchemasNotList = 'KGN-DIAGNOSTICS-1005';
    case InvalidFieldSchemaEntry = 'KGN-DIAGNOSTICS-1006';
    case TooManyFieldSchemas = 'KGN-DIAGNOSTICS-1007';
    case DuplicateFieldSchemaKey = 'KGN-DIAGNOSTICS-1008';
    case InvalidContextValueKey = 'KGN-DIAGNOSTICS-1009';
    case MissingRequiredContextValue = 'KGN-DIAGNOSTICS-1010';
    case ContextValueKindMismatch = 'KGN-DIAGNOSTICS-1011';

    public function title(): string
    {
        return match ($this) {
            self::InvalidFieldKey => 'Diagnostic field key violates the canonical key contract',
            self::InvalidFieldLabel => 'Diagnostic field label violates the fixed-label contract',
            self::InvalidOperation => 'Diagnostic context operation violates its prose contract',
            self::InvalidInvariant => 'Diagnostic context invariant violates its prose contract',
            self::FieldSchemasNotList => 'Diagnostic field schemas are not a zero-based consecutive list',
            self::InvalidFieldSchemaEntry => 'Diagnostic schema list contains a non-field-schema entry',
            self::TooManyFieldSchemas => 'Diagnostic context schema exceeds the field limit',
            self::DuplicateFieldSchemaKey => 'Diagnostic context schema contains a duplicate field key',
            self::InvalidContextValueKey => 'Diagnostic context values contain an unapproved field key',
            self::MissingRequiredContextValue => 'Diagnostic context is missing a required field value',
            self::ContextValueKindMismatch => 'Diagnostic field value does not match its schema kind',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidFieldKey => 'Use non-empty lowercase ASCII snake case containing at most 64 bytes',
            self::InvalidFieldLabel => 'Use non-empty valid UTF-8 containing at most 96 bytes',
            self::InvalidOperation => 'Use non-empty valid UTF-8 containing at most 160 bytes',
            self::InvalidInvariant => 'Use non-empty valid UTF-8 containing at most 256 bytes',
            self::FieldSchemasNotList => 'Reindex field schemas as a PHP list',
            self::InvalidFieldSchemaEntry => 'Replace the reported entry with a DiagnosticFieldSchema',
            self::TooManyFieldSchemas => 'Define at most 16 field schemas',
            self::DuplicateFieldSchemaKey => 'Keep each canonical field key exactly once',
            self::InvalidContextValueKey => 'Supply values only for exact keys in the closed schema',
            self::MissingRequiredContextValue => 'Supply every value required by the closed schema',
            self::ContextValueKindMismatch => 'Supply the exact string, integer, or boolean type required by the field schema',
        };
    }
}
