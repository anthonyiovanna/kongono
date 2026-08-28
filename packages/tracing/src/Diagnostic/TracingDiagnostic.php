<?php

declare(strict_types=1);

namespace Kongono\Tracing\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Phase 10C tracing failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum TracingDiagnostic: string
{
    case Value = 'KGN-TRACING-1001';
    case Lifecycle = 'KGN-TRACING-1002';
    case Collaborator = 'KGN-TRACING-1003';
    case Recording = 'KGN-TRACING-1004';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Execute explicit operation-scoped tracing',
                'Tracing failures must remain bounded and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'tracing',
                'failure_category' => $this->category(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function category(): string
    {
        return match ($this) {
            self::Value => 'value',
            self::Lifecycle => 'lifecycle',
            self::Collaborator => 'collaborator',
            self::Recording => 'recording',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Value => 'Invalid tracing value',
            self::Lifecycle => 'Invalid tracing lifecycle operation',
            self::Collaborator => 'Tracing collaborator failed',
            self::Recording => 'Trace observation recording failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Value => 'Provide exact identifiers, attributes, status, and monotonic timing within the documented bounds.',
            self::Lifecycle => 'Use only active handles owned by the same operation and retire them within the documented capacity.',
            self::Collaborator => 'Inspect the retained collaborator failure and correct the caller-owned implementation.',
            self::Recording => 'Inspect the retained recording failure; completion is final and must not be retried.',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::Value => 'correct_value',
            self::Lifecycle => 'correct_lifecycle',
            self::Collaborator => 'inspect_collaborator',
            self::Recording => 'inspect_recording',
        };
    }
}
