<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ViewContract\Diagnostic\ViewContractDiagnostic;
use PHPUnit\Framework\TestCase;

final class ViewContractDiagnosticTest extends TestCase
{
    public function testIdentifiersAndClosedSafeSchemasAreExactAndComplete(): void
    {
        self::assertSame([
            'KGN-VIEW-CONTRACT-1801',
            'KGN-VIEW-CONTRACT-1802',
            'KGN-VIEW-CONTRACT-1803',
            'KGN-VIEW-CONTRACT-1804',
            'KGN-VIEW-CONTRACT-1805',
            'KGN-VIEW-CONTRACT-1806',
            'KGN-VIEW-CONTRACT-1807',
            'KGN-VIEW-CONTRACT-1808',
            'KGN-VIEW-CONTRACT-1809',
        ], array_column(ViewContractDiagnostic::cases(), 'value'));

        foreach (ViewContractDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $schema = $diagnostic->contextSchema();
            self::assertNotSame('', $schema->operation());
            self::assertNotSame('', $schema->violatedInvariant());
            self::assertSame(
                ['violation', 'entry_count', 'entry_position', 'limit', 'depth', 'value_kind', 'state'],
                array_map(static fn($field): string => $field->key(), $schema->fields()),
            );
            foreach ($schema->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
