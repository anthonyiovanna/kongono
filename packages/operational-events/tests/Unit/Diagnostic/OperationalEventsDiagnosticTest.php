<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\OperationalEvents\Diagnostic\OperationalEventsDiagnostic;
use PHPUnit\Framework\TestCase;

final class OperationalEventsDiagnosticTest extends TestCase
{
    public function testExactIdentifiersAndSafeFixedContextsAreClosed(): void
    {
        self::assertSame(['KGN-OP-EVENTS-1001', 'KGN-OP-EVENTS-1002', 'KGN-OP-EVENTS-1003'], array_column(OperationalEventsDiagnostic::cases(), 'value'));
        foreach (OperationalEventsDiagnostic::cases() as $diagnostic) {
            self::assertSame(['package', 'failure_category', 'remediation'], array_map(static fn($field): string => $field->key(), $diagnostic->context()->fields()));
            self::assertSame(['operational-events', $diagnostic->category(), match ($diagnostic) {
                OperationalEventsDiagnostic::Value => 'correct_value',
                OperationalEventsDiagnostic::Schema => 'correct_schema',
                OperationalEventsDiagnostic::Recording => 'inspect_recorder',
            }], array_map(static fn($field): bool|int|string => $field->value(), $diagnostic->context()->fields()));
            foreach ($diagnostic->context()->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
