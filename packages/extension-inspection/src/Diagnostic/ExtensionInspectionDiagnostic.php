<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns fixed non-disclosing diagnostics for detached inspection. */
#[Api(ApiStatus::Internal)]
enum ExtensionInspectionDiagnostic: string
{
    case InvalidBoundedValue = 'KGN-EXTENSION-INSPECTION-1001';
    case DeclarationArtifactUnavailable = 'KGN-EXTENSION-INSPECTION-1002';
    case GeneratedWiringUnavailable = 'KGN-EXTENSION-INSPECTION-1003';
    case StructurallyInvalidInput = 'KGN-EXTENSION-INSPECTION-1004';
    case DeclarationSetMismatch = 'KGN-EXTENSION-INSPECTION-1005';
    case InconsistentAggregate = 'KGN-EXTENSION-INSPECTION-1006';
    case IndependentVerificationFailed = 'KGN-EXTENSION-INSPECTION-1007';

    public function context(string $component): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Inspect detached extension-platform evidence',
                'Only structurally valid identity-bound Safe aggregate evidence may cross this boundary',
                [new DiagnosticFieldSchema('component', 'Component', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
            ),
            ['component' => $component],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidBoundedValue => 'Extension inspection value is invalid',
            self::DeclarationArtifactUnavailable => 'Extension declaration artifact is unavailable',
            self::GeneratedWiringUnavailable => 'Generated extension wiring is unavailable',
            self::StructurallyInvalidInput => 'Extension inspection input is structurally invalid',
            self::DeclarationSetMismatch => 'Extension declaration-set identities do not match',
            self::InconsistentAggregate => 'Extension inspection aggregate is inconsistent',
            self::IndependentVerificationFailed => 'Extension intelligence verification value is invalid',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidBoundedValue => 'Supply exact bounded values in the required canonical order.',
            self::DeclarationArtifactUnavailable => 'Supply one explicit safe readable declaration artifact file.',
            self::GeneratedWiringUnavailable => 'Supply one explicit safe readable generated wiring file.',
            self::StructurallyInvalidInput => 'Supply inputs accepted by both package-owned structural validators.',
            self::DeclarationSetMismatch => 'Supply declaration and wiring evidence for the same declaration set.',
            self::InconsistentAggregate => 'Supply structurally consistent canonical declaration aggregate evidence.',
            self::IndependentVerificationFailed => 'Supply exact ordered checks, verdict, and fixed failure tokens.',
        };
    }
}
