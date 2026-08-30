<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns bounded non-disclosing metadata for Graph construction failures. */
#[Api(ApiStatus::Internal)]
enum GraphBuildDiagnostic: string
{
    case InvalidInput = 'KGN-GRAPH-1106';
    case InvalidFieldSet = 'KGN-GRAPH-1107';
    case Duplicate = 'KGN-GRAPH-1108';
    case InvalidEndpoint = 'KGN-GRAPH-1109';
    case BoundExceeded = 'KGN-GRAPH-1110';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('record_kind', 'Record kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
        ];

        if (self::InvalidInput === $this || self::InvalidFieldSet === $this || self::BoundExceeded === $this) {
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
            self::InvalidInput => 'Application Graph IR input is invalid',
            self::InvalidFieldSet => 'Application Graph IR field set is invalid',
            self::Duplicate => 'Application Graph IR identity or relation tuple is duplicated',
            self::InvalidEndpoint => 'Application Graph relation endpoint is invalid',
            self::BoundExceeded => 'Application Graph construction bound is exceeded',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInput => 'Supply complete typed version-1 IR record lists in the documented canonical form',
            self::InvalidFieldSet => 'Supply exactly the fields required and permitted by the version-1 schema catalog',
            self::Duplicate => 'Supply unique node identities, relation identities, and relation tuples',
            self::InvalidEndpoint => 'Supply existing endpoints whose node types satisfy the relation schema',
            self::BoundExceeded => 'Reduce the supplied record or field list to the documented construction bound',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidInput => 'Validate Application Graph IR input',
            self::InvalidFieldSet => 'Admit Application Graph IR fields',
            self::Duplicate => 'Validate Application Graph IR uniqueness',
            self::InvalidEndpoint => 'Validate Application Graph relation endpoints',
            self::BoundExceeded => 'Validate Application Graph construction bound',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidInput => 'IR input must be complete, typed, canonical, and supported',
            self::InvalidFieldSet => 'IR fields must exactly satisfy their versioned schema',
            self::Duplicate => 'Graph identities and relation tuples must be unique',
            self::InvalidEndpoint => 'Relation endpoints must exist and satisfy their schema types',
            self::BoundExceeded => 'Construction input must remain within fixed documented bounds',
        };
    }
}
