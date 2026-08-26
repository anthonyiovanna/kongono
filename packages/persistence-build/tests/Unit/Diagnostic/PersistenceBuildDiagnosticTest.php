<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use PHPUnit\Framework\TestCase;

final class PersistenceBuildDiagnosticTest extends TestCase
{
    public function testTwentyDiagnosticsAreUniqueActionableAndSafe(): void
    {
        self::assertCount(20, PersistenceBuildDiagnostic::cases());
        self::assertCount(20, array_unique(array_column(PersistenceBuildDiagnostic::cases(), 'value')));
        foreach (PersistenceBuildDiagnostic::cases() as $diagnostic) {
            self::assertMatchesRegularExpression('/^KGN-PERSISTENCE-BUILD-10(?:0[1-9]|1[0-9]|20)$/D', $diagnostic->value);
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            foreach ($diagnostic->context('source_schema')->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
