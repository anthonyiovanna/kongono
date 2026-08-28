<?php

declare(strict_types=1);

namespace Kongono\Tracing\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Tracing\Diagnostic\TracingDiagnostic;
use PHPUnit\Framework\TestCase;

final class TracingDiagnosticTest extends TestCase
{
    public function testClosedIdentitiesAndEagerSafeContextsAreExact(): void
    {
        self::assertSame(
            ['KGN-TRACING-1001', 'KGN-TRACING-1002', 'KGN-TRACING-1003', 'KGN-TRACING-1004'],
            array_column(TracingDiagnostic::cases(), 'value'),
        );

        foreach (TracingDiagnostic::cases() as $diagnostic) {
            $context = $diagnostic->context();
            self::assertSame(['package', 'failure_category', 'remediation'], array_map(static fn($field): string => $field->key(), $context->fields()));
            foreach ($context->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
        }
    }
}
