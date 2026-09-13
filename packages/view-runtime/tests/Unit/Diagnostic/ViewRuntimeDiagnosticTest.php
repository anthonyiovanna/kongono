<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ViewRuntime\Diagnostic\ViewRuntimeDiagnostic;
use PHPUnit\Framework\TestCase;

final class ViewRuntimeDiagnosticTest extends TestCase
{
    public function testClosedDiagnosticVocabularyAndDisclosureAreExact(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-VIEW-RUNTIME-' . $number, range(1830, 1837)), array_column(ViewRuntimeDiagnostic::cases(), 'value'));
        foreach (ViewRuntimeDiagnostic::cases() as $diagnostic) {
            $fields = $diagnostic->contextSchema()->fields();
            self::assertSame(['violation', 'limit', 'observed_count', 'operation_index', 'output_context', 'primary_failure_kind', 'view_identity', 'sha256'], array_map(static fn($field): string => $field->key(), $fields));
            self::assertSame(DiagnosticDisclosure::Protected, $fields[6]->disclosure());
            self::assertSame(DiagnosticDisclosure::Protected, $fields[7]->disclosure());
        }
    }
}
