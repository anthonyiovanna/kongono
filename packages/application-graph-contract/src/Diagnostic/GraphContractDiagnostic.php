<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns fixed non-disclosing metadata for Application Graph contract failures. */
#[Api(ApiStatus::Internal)]
enum GraphContractDiagnostic: string
{
    case InvalidIdentity = 'KGN-GRAPH-1101';
    case InvalidFieldName = 'KGN-GRAPH-1102';
    case InvalidSchema = 'KGN-GRAPH-1103';
    case InvalidProvenance = 'KGN-GRAPH-1104';
    case ContractLimitExceeded = 'KGN-GRAPH-1105';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = [
            new DiagnosticFieldSchema(
                'violation',
                'Violation',
                DiagnosticValueKind::Text,
                DiagnosticDisclosure::Safe,
            ),
        ];

        if (self::InvalidSchema === $this || self::ContractLimitExceeded === $this) {
            $fields[] = new DiagnosticFieldSchema(
                'entry_count',
                'Entry count',
                DiagnosticValueKind::Integer,
                DiagnosticDisclosure::Safe,
                false,
            );
            $fields[] = new DiagnosticFieldSchema(
                'entry_position',
                'Entry position',
                DiagnosticValueKind::Integer,
                DiagnosticDisclosure::Safe,
                false,
            );
        }

        return new DiagnosticContextSchema($this->operation(), $this->invariant(), $fields);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Application Graph identity digest is invalid',
            self::InvalidFieldName => 'Application Graph field name is invalid',
            self::InvalidSchema => 'Application Graph schema is invalid',
            self::InvalidProvenance => 'Application Graph provenance is invalid',
            self::ContractLimitExceeded => 'Application Graph contract limit is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Supply exactly 64 lowercase ASCII hexadecimal digest bytes',
            self::InvalidFieldName => 'Supply 1 through 64 bytes matching lowercase ASCII field-name grammar',
            self::InvalidSchema => 'Supply the exact canonical list, entry types, uniqueness, order, and endpoints',
            self::InvalidProvenance => 'Supply only the exact bounded producer and source provenance fields',
            self::ContractLimitExceeded => 'Reduce the supplied canonical list to the documented contract bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Create Application Graph identity',
            self::InvalidFieldName => 'Create Application Graph field name',
            self::InvalidSchema => 'Create Application Graph schema',
            self::InvalidProvenance => 'Create Application Graph provenance',
            self::ContractLimitExceeded => 'Validate Application Graph contract bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidIdentity => 'Identity digest must satisfy the exact category-neutral digest grammar',
            self::InvalidFieldName => 'Field name must satisfy the exact lowercase ASCII grammar',
            self::InvalidSchema => 'Schema input must be typed, canonical, unique, ordered, and structurally valid',
            self::InvalidProvenance => 'Provenance must contain only exact bounded non-secret evidence',
            self::ContractLimitExceeded => 'Contract list size must remain within its documented maximum',
        };
    }
}
