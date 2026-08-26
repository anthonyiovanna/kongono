<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Tests\Unit\Diagnostic;

use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class ConsoleBuildDiagnosticTest extends TestCase
{
    public function testCatalogAndSafeContextAreExactAndNonDisclosing(): void
    {
        self::assertSame([
            'KGN-CONSOLE-BUILD-1001',
            'KGN-CONSOLE-BUILD-1002',
            'KGN-CONSOLE-BUILD-1003',
            'KGN-CONSOLE-BUILD-1004',
            'KGN-CONSOLE-BUILD-1005',
        ], array_column(ConsoleBuildDiagnostic::cases(), 'value'));

        $secret = 'never-disclose-this-command-value';
        foreach (ConsoleBuildDiagnostic::cases() as $diagnostic) {
            $context = $diagnostic->context('fixed_category');
            self::assertNotSame('', $diagnostic->operation());
            self::assertNotSame('', $diagnostic->violatedInvariant());
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertSame(['schema_version', 'failure_category', 'artifact_current'], array_map(static fn($field): string => $field->key(), $context->fields()));
            foreach ($context->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
                self::assertStringNotContainsString($secret, (string) $field->value());
            }
        }
    }
}
