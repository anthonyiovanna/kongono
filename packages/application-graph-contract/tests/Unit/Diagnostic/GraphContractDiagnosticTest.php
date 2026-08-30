<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Tests\Unit\Diagnostic;

use Kongono\ApplicationGraphContract\Diagnostic\GraphContractDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class GraphContractDiagnosticTest extends TestCase
{
    public function testDiagnosticCatalogHasExactContiguousIdentitiesAndBoundedSchemas(): void
    {
        self::assertSame([
            ['InvalidIdentity', 'KGN-GRAPH-1101'],
            ['InvalidFieldName', 'KGN-GRAPH-1102'],
            ['InvalidSchema', 'KGN-GRAPH-1103'],
            ['InvalidProvenance', 'KGN-GRAPH-1104'],
            ['ContractLimitExceeded', 'KGN-GRAPH-1105'],
        ], array_map(
            static fn(GraphContractDiagnostic $diagnostic): array => [$diagnostic->name, $diagnostic->value],
            GraphContractDiagnostic::cases(),
        ));

        foreach (GraphContractDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $keys = [];
            foreach ($diagnostic->contextSchema()->fields() as $field) {
                $keys[] = $field->key();
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
            self::assertContains('violation', $keys);
            foreach (['digest', 'field_name', 'symbol', 'path', 'source_body', 'raw_value'] as $forbidden) {
                self::assertNotContains($forbidden, $keys);
            }
        }
    }
}
