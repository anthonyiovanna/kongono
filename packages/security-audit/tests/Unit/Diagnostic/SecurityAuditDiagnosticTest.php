<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\SecurityAudit\Diagnostic\SecurityAuditDiagnostic;
use PHPUnit\Framework\TestCase;

final class SecurityAuditDiagnosticTest extends TestCase
{
    public function testExactIdentifiersAndSafeFixedContextsAreClosed(): void
    {
        self::assertSame(['KGN-SEC-AUDIT-1001', 'KGN-SEC-AUDIT-1002', 'KGN-SEC-AUDIT-1003'], array_column(SecurityAuditDiagnostic::cases(), 'value'));
        foreach (SecurityAuditDiagnostic::cases() as $diagnostic) {
            self::assertSame(['package', 'failure_category', 'remediation'], array_map(static fn($field): string => $field->key(), $diagnostic->context()->fields()));
            foreach ($diagnostic->context()->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
                self::assertStringNotContainsString('protected', (string) $field->value());
            }
        }
    }
}
