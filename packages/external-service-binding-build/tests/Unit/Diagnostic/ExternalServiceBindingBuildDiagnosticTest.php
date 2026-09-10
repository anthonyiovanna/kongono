<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExternalServiceBindingBuild\Diagnostic\ExternalServiceBindingBuildDiagnostic;
use PHPUnit\Framework\TestCase;

final class ExternalServiceBindingBuildDiagnosticTest extends TestCase
{
    public function testDiagnosticInventoryIdsContractsAndSafeSchemaAreClosed(): void
    {
        self::assertSame([
            'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1609', 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1610',
            'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1611', 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1612',
            'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1613', 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1614',
            'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1615', 'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1616',
            'KGN-EXTERNAL-SERVICE-BINDING-BUILD-1617',
        ], array_map(static fn(ExternalServiceBindingBuildDiagnostic $case): string => $case->value, ExternalServiceBindingBuildDiagnostic::cases()));
        $titles = [];
        foreach (ExternalServiceBindingBuildDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $titles[] = $diagnostic->title();
            $fields = $diagnostic->contextSchema()->fields();
            self::assertSame(['violation', 'field', 'entry_count', 'entry_position', 'limit', 'artifact_bytes'], array_map(static fn($field): string => $field->key(), $fields));
            foreach ($fields as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
        self::assertCount(9, array_unique($titles));
    }
}
