<?php

declare(strict_types=1);

namespace Kongono\EventContract\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\EventContract\Diagnostic\EventContractDiagnostic;
use PHPUnit\Framework\TestCase;

final class EventContractDiagnosticTest extends TestCase
{
    public function testClosedCodeCatalogAndOnlySafeViolationField(): void
    {
        self::assertSame([
            'KGN-EVENT-CONTRACT-2211',
            'KGN-EVENT-CONTRACT-2212',
            'KGN-EVENT-CONTRACT-2213',
            'KGN-EVENT-CONTRACT-2214',
        ], array_column(EventContractDiagnostic::cases(), 'value'));

        foreach (EventContractDiagnostic::cases() as $diagnostic) {
            $schema = $diagnostic->contextSchema();
            self::assertCount(1, $schema->fields());
            self::assertSame('violation', $schema->fields()[0]->key());
            self::assertSame(DiagnosticDisclosure::Safe, $schema->fields()[0]->disclosure());
            self::assertNotSame('', $schema->operation());
            self::assertNotSame('', $schema->violatedInvariant());
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertSame('unsupported_violation', $diagnostic->context('PRIVATE-INPUT')->fields()[0]->value());
        }
    }

    public function testOperationCatalogAndSharedBoundViolationOperation(): void
    {
        self::assertSame('Create event registration', EventContractDiagnostic::InvalidEventType->contextSchema()->operation());
        self::assertSame('Create event registration set', EventContractDiagnostic::InvalidRegistration->contextSchema()->operation());
        self::assertSame('Create event registration set', EventContractDiagnostic::DuplicateBinding->contextSchema()->operation());
        self::assertSame('Validate event contract bounds', EventContractDiagnostic::ContractBoundExceeded->contextSchema()->operation());

        foreach (['event_type_bytes', 'registration_count'] as $violation) {
            $context = EventContractDiagnostic::ContractBoundExceeded->context($violation);
            self::assertSame('Validate event contract bounds', $context->operation());
            self::assertSame($violation, $context->fields()[0]->value());
        }
    }
}
