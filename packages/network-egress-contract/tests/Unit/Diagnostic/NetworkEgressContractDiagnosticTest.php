<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\NetworkEgressContract\Diagnostic\NetworkEgressContractDiagnostic;
use PHPUnit\Framework\TestCase;

final class NetworkEgressContractDiagnosticTest extends TestCase
{
    public function testClosedIdentifiersAndSafeSchemaAreExact(): void
    {
        self::assertSame([
            'KGN-EGRESS-CONTRACT-1501',
            'KGN-EGRESS-CONTRACT-1502',
            'KGN-EGRESS-CONTRACT-1503',
            'KGN-EGRESS-CONTRACT-1504',
            'KGN-EGRESS-CONTRACT-1505',
            'KGN-EGRESS-CONTRACT-1506',
            'KGN-EGRESS-CONTRACT-1507',
            'KGN-EGRESS-CONTRACT-1508',
        ], array_column(NetworkEgressContractDiagnostic::cases(), 'value'));

        foreach (NetworkEgressContractDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $fields = $diagnostic->contextSchema()->fields();
            self::assertSame(
                ['violation', 'entry_count', 'entry_position', 'limit', 'outcome', 'state'],
                array_map(static fn($field): string => $field->key(), $fields),
            );
            foreach ($fields as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
