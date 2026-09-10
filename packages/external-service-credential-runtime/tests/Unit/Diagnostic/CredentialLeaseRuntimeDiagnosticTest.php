<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExternalServiceCredentialRuntime\Diagnostic\CredentialLeaseRuntimeDiagnostic;
use PHPUnit\Framework\TestCase;

final class CredentialLeaseRuntimeDiagnosticTest extends TestCase
{
    public function testIdsTitlesRemediationOperationsInvariantsAndSchemasAreClosed(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-EXTERNAL-SERVICE-CREDENTIAL-RUNTIME-' . $number, range(1601, 1607)), array_column(CredentialLeaseRuntimeDiagnostic::cases(), 'value'));
        foreach (CredentialLeaseRuntimeDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $schema = $diagnostic->contextSchema();
            self::assertNotSame('', $schema->operation());
            self::assertNotSame('', $schema->violatedInvariant());
            foreach ($schema->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
                self::assertContains($field->key(), ['stage', 'violation', 'outcome', 'state', 'limit', 'observed_count', 'acquisition_attempt_count', 'material_access_attempt_count', 'retirement_attempt_count', 'cleanup_complete']);
            }
        }
    }
}
