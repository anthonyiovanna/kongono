<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Diagnostic;

use Kongono\CacheContract\Diagnostic\CacheContractDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class CacheContractDiagnosticTest extends TestCase
{
    public function testIdentifiersFixedTextAndClosedSafeSchemasAreExact(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-CACHE-CONTRACT-' . $number, range(2001, 2006)), array_column(CacheContractDiagnostic::cases(), 'value'));
        foreach (CacheContractDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $schema = $diagnostic->contextSchema();
            self::assertNotSame('', $schema->operation());
            self::assertNotSame('', $schema->violatedInvariant());
            self::assertSame(['violation', 'byte_count', 'limit', 'operation', 'outcome'], array_map(static fn($field): string => $field->key(), $schema->fields()));
            foreach ($schema->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
