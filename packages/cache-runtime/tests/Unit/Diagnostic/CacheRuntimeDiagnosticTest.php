<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Unit\Diagnostic;

use Kongono\CacheRuntime\Diagnostic\CacheRuntimeDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class CacheRuntimeDiagnosticTest extends TestCase
{
    public function testCodesTextAndSafeSchemaAreExact(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-CACHE-RUNTIME-' . $number, range(2031, 2035)), array_column(CacheRuntimeDiagnostic::cases(), 'value'));
        foreach (CacheRuntimeDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertSame(['violation', 'operation', 'outcome', 'byte_count', 'limit', 'active'], array_map(static fn($field): string => $field->key(), $diagnostic->contextSchema()->fields()));
            foreach ($diagnostic->contextSchema()->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
