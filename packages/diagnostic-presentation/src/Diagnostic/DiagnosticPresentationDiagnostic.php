<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns context-free metadata for trusted projection construction failures. */
#[Api(ApiStatus::Internal)]
enum DiagnosticPresentationDiagnostic: string
{
    case AmbiguousDiagnosticOwner = 'KGN-PRESENTATION-1001';
    case InvalidDiagnosticIdentifier = 'KGN-PRESENTATION-1002';
    case InvalidDiagnosticTitle = 'KGN-PRESENTATION-1003';
    case InvalidDiagnosticRemediation = 'KGN-PRESENTATION-1004';
    case InvalidDiagnosticOperation = 'KGN-PRESENTATION-1005';
    case InvalidDiagnosticInvariant = 'KGN-PRESENTATION-1006';
    case DiagnosticFieldsNotList = 'KGN-PRESENTATION-1007';
    case InvalidDiagnosticFieldEntry = 'KGN-PRESENTATION-1008';
    case TooManyDiagnosticFields = 'KGN-PRESENTATION-1009';
    case DuplicateDiagnosticFieldKey = 'KGN-PRESENTATION-1010';
    case InvalidDiagnosticFieldKey = 'KGN-PRESENTATION-1011';
    case InvalidDiagnosticFieldLabel = 'KGN-PRESENTATION-1012';
    case DiagnosticFieldValueKindMismatch = 'KGN-PRESENTATION-1013';
    case InvalidDiagnosticTextRepresentation = 'KGN-PRESENTATION-1014';
    case InvalidDiagnosticTruncationState = 'KGN-PRESENTATION-1015';

    public function title(): string
    {
        return match ($this) {
            self::AmbiguousDiagnosticOwner => 'Ambiguous diagnostic owner',
            self::InvalidDiagnosticIdentifier => 'Invalid diagnostic identifier',
            self::InvalidDiagnosticTitle => 'Invalid diagnostic title',
            self::InvalidDiagnosticRemediation => 'Invalid diagnostic remediation',
            self::InvalidDiagnosticOperation => 'Invalid diagnostic operation',
            self::InvalidDiagnosticInvariant => 'Invalid diagnostic invariant',
            self::DiagnosticFieldsNotList => 'Diagnostic fields are not a list',
            self::InvalidDiagnosticFieldEntry => 'Invalid diagnostic field entry',
            self::TooManyDiagnosticFields => 'Too many diagnostic fields',
            self::DuplicateDiagnosticFieldKey => 'Duplicate diagnostic field key',
            self::InvalidDiagnosticFieldKey => 'Invalid diagnostic field key',
            self::InvalidDiagnosticFieldLabel => 'Invalid diagnostic field label',
            self::DiagnosticFieldValueKindMismatch => 'Diagnostic field value kind mismatch',
            self::InvalidDiagnosticTextRepresentation => 'Invalid diagnostic text representation',
            self::InvalidDiagnosticTruncationState => 'Invalid diagnostic truncation state',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::AmbiguousDiagnosticOwner => 'Use a source that implements exactly one supported package exception marker',
            self::InvalidDiagnosticIdentifier => 'Return the exact owner prefix followed by four ASCII decimal digits',
            self::InvalidDiagnosticTitle => 'Return a non-empty valid UTF-8 title containing at most 160 bytes',
            self::InvalidDiagnosticRemediation => 'Return non-empty valid UTF-8 remediation containing at most 256 bytes',
            self::InvalidDiagnosticOperation => 'Return a non-empty valid UTF-8 operation containing at most 160 bytes',
            self::InvalidDiagnosticInvariant => 'Return a non-empty valid UTF-8 invariant containing at most 256 bytes',
            self::DiagnosticFieldsNotList => 'Supply fields as a zero-based consecutive list',
            self::InvalidDiagnosticFieldEntry => 'Supply only trusted developer diagnostic field values',
            self::TooManyDiagnosticFields => 'Limit one projected diagnostic to at most 16 fields',
            self::DuplicateDiagnosticFieldKey => 'Use each canonical diagnostic field key at most once',
            self::InvalidDiagnosticFieldKey => 'Use lowercase ASCII snake case containing at most 64 bytes',
            self::InvalidDiagnosticFieldLabel => 'Use a non-empty valid UTF-8 label containing at most 96 bytes',
            self::DiagnosticFieldValueKindMismatch => 'Match the scalar value to the exact diagnostic value kind',
            self::InvalidDiagnosticTextRepresentation => 'Use printable ASCII represented text containing at most 512 bytes',
            self::InvalidDiagnosticTruncationState => 'Use a boolean state for text, null for other kinds, and the truncation suffix when true',
        };
    }
}
