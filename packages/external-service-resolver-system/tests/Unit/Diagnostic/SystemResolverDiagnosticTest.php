<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExternalServiceResolverSystem\Diagnostic\SystemResolverDiagnostic;
use PHPUnit\Framework\TestCase;

final class SystemResolverDiagnosticTest extends TestCase
{
    public function testIdsAndContextAreClosedAndSafe(): void
    {
        self::assertSame([
            'KGN-RESOLVER-1618', 'KGN-RESOLVER-1619', 'KGN-RESOLVER-1620',
            'KGN-RESOLVER-1621', 'KGN-RESOLVER-1622', 'KGN-RESOLVER-1623',
        ], array_column(SystemResolverDiagnostic::cases(), 'value'));
        foreach (SystemResolverDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            foreach ($diagnostic->contextSchema()->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
        $context = SystemResolverDiagnostic::SystemResultRejected->context([
            'stage' => 'normalization', 'violation' => 'foreign-secret.invalid', 'count' => 999,
        ]);
        self::assertStringNotContainsString('foreign-secret.invalid', serialize($context));
    }
}
