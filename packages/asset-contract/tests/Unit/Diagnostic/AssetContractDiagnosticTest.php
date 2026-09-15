<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Tests\Unit\Diagnostic;

use Kongono\AssetContract\Diagnostic\AssetContractDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class AssetContractDiagnosticTest extends TestCase
{
    public function testIdentifiersAndClosedSafeSchemasAreExactAndComplete(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-ASSET-CONTRACT-' . $number, range(1901, 1906)), array_column(AssetContractDiagnostic::cases(), 'value'));
        foreach (AssetContractDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $schema = $diagnostic->contextSchema();
            self::assertNotSame('', $schema->operation());
            self::assertNotSame('', $schema->violatedInvariant());
            self::assertSame(['violation', 'entry_count', 'entry_position', 'limit', 'asset_kind', 'dependency_kind', 'algorithm', 'preload_hint'], array_map(static fn($field): string => $field->key(), $schema->fields()));
            foreach ($schema->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
