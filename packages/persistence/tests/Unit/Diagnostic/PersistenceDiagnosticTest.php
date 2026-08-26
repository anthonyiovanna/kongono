<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Persistence\Diagnostic\PersistenceDiagnostic;
use PHPUnit\Framework\TestCase;

final class PersistenceDiagnosticTest extends TestCase
{
    public function testClosedIdentifiersAndSafeBoundedContextsAreExact(): void
    {
        self::assertSame(
            array_map(static fn(int $ordinal): string => sprintf('KGN-PERSISTENCE-%04d', $ordinal), range(1001, 1009)),
            array_map(static fn(PersistenceDiagnostic $diagnostic): string => $diagnostic->value, PersistenceDiagnostic::cases()),
        );

        foreach (PersistenceDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertNotSame('', $diagnostic->category());
            $context = $diagnostic->context();
            self::assertSame('Use operation-scoped persistence', $context->operation());
            self::assertSame('Persistence failures must remain explicit, bounded, and non-disclosing', $context->violatedInvariant());
            self::assertSame(['package', 'failure_category', 'remediation'], array_map(static fn($field): string => $field->key(), $context->fields()));
            self::assertSame(['persistence', $diagnostic->category()], [$context->fields()[0]->value(), $context->fields()[1]->value()]);
            foreach ($context->fields() as $field) {
                self::assertSame(DiagnosticValueKind::Text, $field->kind());
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
                self::assertLessThanOrEqual(64, strlen((string) $field->value()));
            }
        }
    }
}
