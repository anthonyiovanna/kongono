<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Tests\Unit\Diagnostic;

use Kongono\ConsoleRuntime\Diagnostic\ConsoleRuntimeDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class ConsoleRuntimeDiagnosticTest extends TestCase
{
    public function testCatalogAndSafeContextsAreExact(): void
    {
        self::assertSame([
            'KGN-CONSOLE-RUNTIME-1001',
            'KGN-CONSOLE-RUNTIME-1002',
            'KGN-CONSOLE-RUNTIME-1003',
            'KGN-CONSOLE-RUNTIME-1004',
            'KGN-CONSOLE-RUNTIME-1005',
            'KGN-CONSOLE-RUNTIME-1006',
        ], array_column(ConsoleRuntimeDiagnostic::cases(), 'value'));

        foreach (ConsoleRuntimeDiagnostic::cases() as $diagnostic) {
            $context = $diagnostic->context(true, 3);
            self::assertSame('Execute one command operation', $context->operation());
            self::assertSame(
                ['package', 'failure_category', 'primary_failure', 'cleanup_failure_count', 'remediation'],
                array_map(static fn($field): string => $field->key(), $context->fields()),
            );
            foreach ($context->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
