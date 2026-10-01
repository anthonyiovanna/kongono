<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\EventRuntime\Diagnostic\EventRuntimeDiagnostic;
use PHPUnit\Framework\TestCase;

final class EventRuntimeDiagnosticTest extends TestCase
{
    public function testClosedCatalogAndSafeContextAreExact(): void
    {
        self::assertSame(['KGN-EVENT-RUNTIME-2221'], array_column(EventRuntimeDiagnostic::cases(), 'value'));
        $diagnostic = EventRuntimeDiagnostic::ReentrantDispatch;
        self::assertSame('Event dispatch is already active', $diagnostic->title());
        self::assertSame('Wait for the active dispatch to finish or use a separate dispatcher instance', $diagnostic->remediation());
        $schema = $diagnostic->contextSchema();
        self::assertSame('Enter synchronous event dispatch', $schema->operation());
        self::assertSame('One dispatcher instance has at most one active dispatch', $schema->violatedInvariant());
        self::assertCount(1, $schema->fields());
        $field = $schema->fields()[0];
        self::assertSame('violation', $field->key());
        self::assertSame('Violation', $field->label());
        self::assertSame(DiagnosticValueKind::Text, $field->kind());
        self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
        self::assertTrue($field->isRequired());
        self::assertSame('reentrant_dispatch', $diagnostic->context()->fields()[0]->value());
    }
}
