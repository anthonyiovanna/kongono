<?php

declare(strict_types=1);

namespace Kongono\Metrics\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Phase 10D metrics failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum MetricsDiagnostic: string
{
    case Value = 'KGN-METRICS-1001';
    case Schema = 'KGN-METRICS-1002';
    case Lifecycle = 'KGN-METRICS-1003';
    case Aggregation = 'KGN-METRICS-1004';
    case Recording = 'KGN-METRICS-1005';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Operate bounded metric collection',
                'Metric failures must remain bounded and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'metrics',
                'failure_category' => $this->category(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function category(): string
    {
        return match ($this) {
            self::Value => 'value',
            self::Schema => 'schema',
            self::Lifecycle => 'lifecycle',
            self::Aggregation => 'aggregation',
            self::Recording => 'recording',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Value => 'Invalid metric value',
            self::Schema => 'Invalid metric schema',
            self::Lifecycle => 'Invalid metric collection lifecycle',
            self::Aggregation => 'Metric aggregation failed',
            self::Recording => 'Metric observation recording failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Value => 'Provide a finite metric value or valid bounded unit or boundary list.',
            self::Schema => 'Provide an exact bounded predeclared metric schema and plan.',
            self::Lifecycle => 'Use an active collection and an instrument owned by its exact plan.',
            self::Aggregation => 'Use measurements whose bounded aggregate remains representable.',
            self::Recording => 'Inspect the retained cause and retire the failed external recorder path.',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::Value => 'correct_value',
            self::Schema => 'correct_schema',
            self::Lifecycle => 'correct_lifecycle',
            self::Aggregation => 'correct_aggregation',
            self::Recording => 'inspect_recorder',
        };
    }
}
