<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ModelInvocationContract\Diagnostic\ModelInvocationContractDiagnostic;
use PHPUnit\Framework\TestCase;

final class ModelInvocationContractDiagnosticTest extends TestCase
{
    public function testIdsMetadataAndClosedSafeContextAreExact(): void
    {
        self::assertSame(['KGN-MODEL-CONTRACT-1401', 'KGN-MODEL-CONTRACT-1402', 'KGN-MODEL-CONTRACT-1403', 'KGN-MODEL-CONTRACT-1404', 'KGN-MODEL-CONTRACT-1405', 'KGN-MODEL-CONTRACT-1406', 'KGN-MODEL-CONTRACT-1407', 'KGN-MODEL-CONTRACT-1408', 'KGN-MODEL-CONTRACT-1409'], array_column(ModelInvocationContractDiagnostic::cases(), 'value'));
        foreach (ModelInvocationContractDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            foreach ($diagnostic->contextSchema()->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
                self::assertContains($field->key(), ['violation', 'entry_count', 'entry_position', 'limit', 'mode', 'event_kind']);
            }
        }
    }
}
