<?php

declare(strict_types=1);

namespace Kongono\Observability\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\Diagnostic\ObservabilityDiagnostic;
use PHPUnit\Framework\TestCase;

final class ObservabilityDiagnosticTest extends TestCase
{
    public function testClosedDiagnosticVocabularyAndSafeFixedContextsAreExact(): void
    {
        $expected = [
            [ObservabilityDiagnostic::Context, 'KGN-OBSERVABILITY-1001', 'context', 'Invalid observability context value'],
            [ObservabilityDiagnostic::Attribute, 'KGN-OBSERVABILITY-1002', 'attribute', 'Invalid observability attribute value'],
            [ObservabilityDiagnostic::SignalIdentity, 'KGN-OBSERVABILITY-1003', 'signal_identity', 'Invalid signal identity'],
        ];
        self::assertCount(3, ObservabilityDiagnostic::cases());
        foreach ($expected as [$diagnostic, $id, $category, $title]) {
            self::assertSame($id, $diagnostic->value);
            self::assertSame($category, $diagnostic->category());
            self::assertSame($title, $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $context = $diagnostic->context();
            self::assertSame('Construct neutral observability values', $context->operation());
            self::assertSame('Observability contract failures must remain bounded and non-disclosing', $context->violatedInvariant());
            self::assertSame(['package', 'failure_category', 'remediation'], array_map(static fn($field): string => $field->key(), $context->fields()));
            foreach ($context->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
            self::assertSame('observability', $context->fields()[0]->value());
            self::assertSame($category, $context->fields()[1]->value());
        }
    }
}
