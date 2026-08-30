<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Tests\Unit\Diagnostic;

use Kongono\ApplicationGraphComposition\Diagnostic\CompositionGraphDiagnostic;
use PHPUnit\Framework\TestCase;

final class CompositionGraphDiagnosticTest extends TestCase
{
    public function testExactClosedDiagnosticsAreUniqueAndSafe(): void
    {
        self::assertSame(
            ['KGN-GRAPH-1111', 'KGN-GRAPH-1112', 'KGN-GRAPH-1113', 'KGN-GRAPH-1114', 'KGN-GRAPH-1115'],
            array_column(CompositionGraphDiagnostic::cases(), 'value'),
        );
        foreach (CompositionGraphDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertSame(['violation', 'record_kind', 'count', 'position'], array_map(
                static fn($field): string => $field->key(),
                $diagnostic->contextSchema()->fields(),
            ));
        }
    }
}
