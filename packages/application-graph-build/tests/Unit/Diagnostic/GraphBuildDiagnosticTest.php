<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Tests\Unit\Diagnostic;

use Kongono\ApplicationGraphBuild\Diagnostic\GraphBuildDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class GraphBuildDiagnosticTest extends TestCase
{
    public function testCatalogAndSafeSchemasAreExact(): void
    {
        self::assertSame([
            ['InvalidInput', 'KGN-GRAPH-1106'],
            ['InvalidFieldSet', 'KGN-GRAPH-1107'],
            ['Duplicate', 'KGN-GRAPH-1108'],
            ['InvalidEndpoint', 'KGN-GRAPH-1109'],
            ['BoundExceeded', 'KGN-GRAPH-1110'],
        ], array_map(static fn(GraphBuildDiagnostic $diagnostic): array => [$diagnostic->name, $diagnostic->value], GraphBuildDiagnostic::cases()));

        foreach (GraphBuildDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $keys = [];
            foreach ($diagnostic->contextSchema()->fields() as $field) {
                $keys[] = $field->key();
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
            self::assertContains('violation', $keys);
            self::assertContains('record_kind', $keys);
            foreach (['identity', 'digest', 'field_name', 'field_value', 'symbol', 'path', 'source_body'] as $forbidden) {
                self::assertNotContains($forbidden, $keys);
            }
        }
    }
}
