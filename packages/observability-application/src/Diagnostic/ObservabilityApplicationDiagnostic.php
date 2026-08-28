<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing application observability failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum ObservabilityApplicationDiagnostic: string
{
    case Lifecycle = 'KGN-OBSERVABILITY-APPLICATION-1001';
    case CombinedFailure = 'KGN-OBSERVABILITY-APPLICATION-1002';

    public function title(): string
    {
        return match ($this) {
            self::Lifecycle => 'Invalid application observability lifecycle',
            self::CombinedFailure => 'Application observability cleanup failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Lifecycle => 'Use the application and operation only in their exact open lifecycle states and bounds.',
            self::CombinedFailure => 'Inspect the retained failure identities and explicitly resolve the failed cleanup.',
        };
    }

    public function lifecycleContext(string $stage): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema('Operate application observability', 'Lifecycle failures remain fixed and non-disclosing', [
                new DiagnosticFieldSchema('category', 'Category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ]),
            ['category' => 'lifecycle', 'stage' => $stage, 'remediation' => 'correct_lifecycle'],
        );
    }

    public function combinedContext(bool $primaryFailure, int $cleanupFailureCount): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema('Complete application observability operation', 'Failure identities remain programmatic and non-disclosing', [
                new DiagnosticFieldSchema('category', 'Category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('primary_failure', 'Primary failure present', DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('cleanup_failure_count', 'Cleanup failure count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ]),
            [
                'category' => 'combined_failure',
                'primary_failure' => $primaryFailure,
                'cleanup_failure_count' => $cleanupFailureCount,
                'remediation' => 'preserve_failures',
            ],
        );
    }
}
