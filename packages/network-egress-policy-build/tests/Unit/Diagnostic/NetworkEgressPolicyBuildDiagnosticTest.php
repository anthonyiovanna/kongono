<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\NetworkEgressPolicyBuild\Diagnostic\NetworkEgressPolicyBuildDiagnostic;
use PHPUnit\Framework\TestCase;

final class NetworkEgressPolicyBuildDiagnosticTest extends TestCase
{
    public function testDiagnosticIdsAndClosedSafeSchemasAreExact(): void
    {
        $cases = NetworkEgressPolicyBuildDiagnostic::cases();
        self::assertSame([
            'KGN-EGRESS-POLICY-1510', 'KGN-EGRESS-POLICY-1511', 'KGN-EGRESS-POLICY-1512',
            'KGN-EGRESS-POLICY-1513', 'KGN-EGRESS-POLICY-1514', 'KGN-EGRESS-POLICY-1515',
            'KGN-EGRESS-POLICY-1516',
        ], array_column($cases, 'value'));
        self::assertCount(7, array_unique(array_column($cases, 'value')));
        foreach ($cases as $case) {
            self::assertNotSame('', $case->title());
            self::assertNotSame('', $case->remediation());
            foreach ($case->contextSchema()->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
                self::assertContains($field->key(), ['violation', 'field', 'entry_count', 'entry_position', 'numeric_limit', 'artifact_bytes']);
            }
        }
    }
}
