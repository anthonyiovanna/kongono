<?php

declare(strict_types=1);

namespace Kongono\Metrics\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Metrics\Diagnostic\MetricsDiagnostic;
use PHPUnit\Framework\TestCase;

final class MetricsDiagnosticTest extends TestCase
{
    public function testExactIdentifiersAndSafeFixedContextsAreClosed(): void
    {
        self::assertSame([
            'KGN-METRICS-1001',
            'KGN-METRICS-1002',
            'KGN-METRICS-1003',
            'KGN-METRICS-1004',
            'KGN-METRICS-1005',
        ], array_column(MetricsDiagnostic::cases(), 'value'));
        foreach (MetricsDiagnostic::cases() as $diagnostic) {
            $fields = $diagnostic->context()->fields();
            self::assertSame(['package', 'failure_category', 'remediation'], array_map(static fn($field): string => $field->key(), $fields));
            self::assertSame(['metrics', $diagnostic->category(), match ($diagnostic) {
                MetricsDiagnostic::Value => 'correct_value',
                MetricsDiagnostic::Schema => 'correct_schema',
                MetricsDiagnostic::Lifecycle => 'correct_lifecycle',
                MetricsDiagnostic::Aggregation => 'correct_aggregation',
                MetricsDiagnostic::Recording => 'inspect_recorder',
            }], array_map(static fn($field): bool|int|string => $field->value(), $fields));
            foreach ($fields as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
