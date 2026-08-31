<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the sole fixed non-disclosing diagnostic for extension admission failures. */
#[Api(ApiStatus::Internal)]
enum ExtensionAdmissionDiagnostic: string
{
    case AdmissionFailed = 'KGN-EXTENSION-ADMISSION-1201';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema(
            'Admit extension Graph contributions',
            'Selection, currentness, conflict, provenance, and Graph admission must be exact',
            [
                new DiagnosticFieldSchema('failure_kind', 'Failure kind', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('entry_count', 'Entry count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('entry_position', 'Entry position', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
                new DiagnosticFieldSchema('extension_identity', 'Extension identity', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
                new DiagnosticFieldSchema('contribution_identity', 'Contribution identity', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected, false),
            ],
        );
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return 'Extension admission failed';
    }

    public function remediation(): string
    {
        return 'Supply exact current canonical declarations, selections, resolutions, and attributable Graph IR';
    }
}
