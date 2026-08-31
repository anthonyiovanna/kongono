<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Tests\Unit\Diagnostic;

use Kongono\ExtensionInspection\Diagnostic\ExtensionInspectionDiagnostic;
use PHPUnit\Framework\TestCase;

final class ExtensionInspectionDiagnosticTest extends TestCase
{
    public function testIdentifiersAreReservedAndContextsAreSafe(): void
    {
        foreach (ExtensionInspectionDiagnostic::cases() as $diagnostic) {
            self::assertMatchesRegularExpression('/\AKGN-EXTENSION-INSPECTION-100[1-7]\z/D', $diagnostic->value);
            self::assertSame('component', $diagnostic->context('count')->fields()[0]->key());
        }
    }
}
