<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Tests\Unit\Diagnostic;

use Kongono\OpenTelemetry\Diagnostic\OpenTelemetryDiagnostic;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryDiagnosticTest extends TestCase
{
    public function testClosedIdentitiesAndContextsAreExactAndNondisclosing(): void
    {
        self::assertSame(
            ['KGN-OTEL-1001', 'KGN-OTEL-1002', 'KGN-OTEL-1003', 'KGN-OTEL-1004', 'KGN-OTEL-1005'],
            array_column(OpenTelemetryDiagnostic::cases(), 'value'),
        );
        $context = OpenTelemetryDiagnostic::Export->context('trace', 2, 8, 'send');
        $values = [];
        foreach ($context->fields() as $field) {
            $values[$field->key()] = $field->value();
        }
        self::assertSame(['category', 'remediation', 'signal_kind', 'queue_count', 'queue_capacity', 'stage'], array_keys($values));
        self::assertStringNotContainsString('payload', json_encode($values, JSON_THROW_ON_ERROR));
    }
}
