<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExternalServiceContract\Diagnostic\ExternalServiceContractDiagnostic;
use PHPUnit\Framework\TestCase;

final class ExternalServiceContractDiagnosticTest extends TestCase
{
    public function testIdentifiersSchemaAndRemediationAreExactAndSafe(): void
    {
        self::assertSame([
            'KGN-EXTERNAL-SERVICE-CONTRACT-1601',
            'KGN-EXTERNAL-SERVICE-CONTRACT-1602',
            'KGN-EXTERNAL-SERVICE-CONTRACT-1603',
            'KGN-EXTERNAL-SERVICE-CONTRACT-1604',
            'KGN-EXTERNAL-SERVICE-CONTRACT-1605',
            'KGN-EXTERNAL-SERVICE-CONTRACT-1606',
            'KGN-EXTERNAL-SERVICE-CONTRACT-1607',
            'KGN-EXTERNAL-SERVICE-CONTRACT-1608',
        ], array_column(ExternalServiceContractDiagnostic::cases(), 'value'));
        foreach (ExternalServiceContractDiagnostic::cases() as $diagnostic) {
            $fields = $diagnostic->contextSchema()->fields();
            self::assertSame(['violation', 'entry_count', 'entry_position', 'limit', 'state', 'outcome'], array_map(static fn($field): string => $field->key(), $fields));
            foreach ($fields as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
        }
    }
}
