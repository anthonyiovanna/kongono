<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExtensionComposition\Diagnostic\ExtensionCompositionDiagnostic;
use PHPUnit\Framework\TestCase;

final class ExtensionCompositionDiagnosticTest extends TestCase
{
    public function testSoleDiagnosticHasExactBoundedDisclosureSchema(): void
    {
        $diagnostic = ExtensionCompositionDiagnostic::CompositionFailed;
        self::assertSame('KGN-EXTENSION-COMPOSITION-1201', $diagnostic->value);
        self::assertNotSame('', $diagnostic->title());
        self::assertNotSame('', $diagnostic->remediation());
        $fields = $diagnostic->contextSchema()->fields();
        self::assertSame(['violation', 'entry_count', 'entry_position'], array_map(static fn($field): string => $field->key(), $fields));
        self::assertSame(DiagnosticDisclosure::Safe, $fields[0]->disclosure());
        self::assertSame(DiagnosticDisclosure::Safe, $fields[1]->disclosure());
        self::assertSame(DiagnosticDisclosure::Protected, $fields[2]->disclosure());
    }
}
