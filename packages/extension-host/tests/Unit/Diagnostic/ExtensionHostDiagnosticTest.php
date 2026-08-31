<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExtensionHost\Diagnostic\ExtensionHostDiagnostic;
use PHPUnit\Framework\TestCase;

final class ExtensionHostDiagnosticTest extends TestCase
{
    public function testExactCatalogIsFixedBoundedAndNondisclosing(): void
    {
        self::assertSame([
            'KGN-EXTENSION-HOST-1201',
            'KGN-EXTENSION-HOST-1202',
            'KGN-EXTENSION-HOST-1203',
        ], array_map(static fn(ExtensionHostDiagnostic $diagnostic): string => $diagnostic->value, ExtensionHostDiagnostic::cases()));
        foreach (ExtensionHostDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            foreach ($diagnostic->contextSchema()->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
