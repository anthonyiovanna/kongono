<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Tests\Unit\Diagnostic;

use Kongono\ConsolePresentation\Diagnostic\ConsolePresentationDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class ConsolePresentationDiagnosticTest extends TestCase
{
    public function testCatalogAndSafeContextsAreExact(): void
    {
        self::assertSame([
            'KGN-CONSOLE-PRESENTATION-1001',
            'KGN-CONSOLE-PRESENTATION-1002',
            'KGN-CONSOLE-PRESENTATION-1003',
            'KGN-CONSOLE-PRESENTATION-1004',
            'KGN-CONSOLE-PRESENTATION-1005',
        ], array_column(ConsolePresentationDiagnostic::cases(), 'value'));

        foreach (ConsolePresentationDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $fields = $diagnostic->context()->fields();
            self::assertSame(['package', 'failure_category', 'remediation'], array_map(static fn($field): string => $field->key(), $fields));
            foreach ($fields as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
