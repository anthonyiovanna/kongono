<?php

declare(strict_types=1);

namespace Kongono\PersistenceApplication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing persistence-application diagnostic contract. */
#[Api(ApiStatus::Internal)]
enum PersistenceApplicationDiagnostic: string
{
    case WorkflowFailed = 'KGN-PERSISTENCE-APPLICATION-1001';

    public function context(string $failureCategory): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            'Build or check generated persistence application',
            'Mapping persistence generated loading and Composition evidence must be exact current ordered contained and unchanged',
            [
                new DiagnosticFieldSchema('schema_version', 'Schema version', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
        ), [
            'schema_version' => 1,
            'failure_category' => $failureCategory,
        ]);
    }

    public function title(): string
    {
        return 'Persistence-application workflow failed';
    }

    public function remediation(): string
    {
        return 'Restore exact current inputs and outputs then rerun the explicit build or check in a fresh process';
    }
}
