<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit\Diagnostic;

use Kongono\Console\Diagnostic\ConsoleDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class ConsoleDiagnosticTest extends TestCase
{
    public function testCatalogIsUniqueExactBoundedAndSafe(): void
    {
        self::assertSame([
            'KGN-CONSOLE-1001',
            'KGN-CONSOLE-1002',
            'KGN-CONSOLE-1003',
            'KGN-CONSOLE-1004',
            'KGN-CONSOLE-1005',
            'KGN-CONSOLE-1006',
            'KGN-CONSOLE-1007',
            'KGN-CONSOLE-1008',
            'KGN-CONSOLE-1009',
            'KGN-CONSOLE-1010',
        ], array_column(ConsoleDiagnostic::cases(), 'value'));

        $categories = [];
        foreach (ConsoleDiagnostic::cases() as $diagnostic) {
            $context = $diagnostic->context();
            self::assertSame('Use neutral Console contracts', $context->operation());
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertNotContains($diagnostic->category(), $categories);
            $categories[] = $diagnostic->category();

            $fields = $context->fields();
            self::assertSame(['package', 'failure_category', 'remediation'], array_map(static fn($field): string => $field->key(), $fields));
            self::assertSame(['console', $diagnostic->category()], [$fields[0]->value(), $fields[1]->value()]);
            foreach ($fields as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
