<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Tests\Unit;

use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class DiagnosticPresentationDiagnosticTest extends TestCase
{
    public function testCatalogCasesIdentifiersTitlesAndRemediationsAreExact(): void
    {
        $expected = [
            ['AmbiguousDiagnosticOwner', 'KGN-PRESENTATION-1001', 'Ambiguous diagnostic owner', 'Use a source that implements exactly one supported package exception marker'],
            ['InvalidDiagnosticIdentifier', 'KGN-PRESENTATION-1002', 'Invalid diagnostic identifier', 'Return the exact owner prefix followed by four ASCII decimal digits'],
            ['InvalidDiagnosticTitle', 'KGN-PRESENTATION-1003', 'Invalid diagnostic title', 'Return a non-empty valid UTF-8 title containing at most 160 bytes'],
            ['InvalidDiagnosticRemediation', 'KGN-PRESENTATION-1004', 'Invalid diagnostic remediation', 'Return non-empty valid UTF-8 remediation containing at most 256 bytes'],
            ['InvalidDiagnosticOperation', 'KGN-PRESENTATION-1005', 'Invalid diagnostic operation', 'Return a non-empty valid UTF-8 operation containing at most 160 bytes'],
            ['InvalidDiagnosticInvariant', 'KGN-PRESENTATION-1006', 'Invalid diagnostic invariant', 'Return a non-empty valid UTF-8 invariant containing at most 256 bytes'],
            ['DiagnosticFieldsNotList', 'KGN-PRESENTATION-1007', 'Diagnostic fields are not a list', 'Supply fields as a zero-based consecutive list'],
            ['InvalidDiagnosticFieldEntry', 'KGN-PRESENTATION-1008', 'Invalid diagnostic field entry', 'Supply only trusted developer diagnostic field values'],
            ['TooManyDiagnosticFields', 'KGN-PRESENTATION-1009', 'Too many diagnostic fields', 'Limit one projected diagnostic to at most 16 fields'],
            ['DuplicateDiagnosticFieldKey', 'KGN-PRESENTATION-1010', 'Duplicate diagnostic field key', 'Use each canonical diagnostic field key at most once'],
            ['InvalidDiagnosticFieldKey', 'KGN-PRESENTATION-1011', 'Invalid diagnostic field key', 'Use lowercase ASCII snake case containing at most 64 bytes'],
            ['InvalidDiagnosticFieldLabel', 'KGN-PRESENTATION-1012', 'Invalid diagnostic field label', 'Use a non-empty valid UTF-8 label containing at most 96 bytes'],
            ['DiagnosticFieldValueKindMismatch', 'KGN-PRESENTATION-1013', 'Diagnostic field value kind mismatch', 'Match the scalar value to the exact diagnostic value kind'],
            ['InvalidDiagnosticTextRepresentation', 'KGN-PRESENTATION-1014', 'Invalid diagnostic text representation', 'Use printable ASCII represented text containing at most 512 bytes'],
            ['InvalidDiagnosticTruncationState', 'KGN-PRESENTATION-1015', 'Invalid diagnostic truncation state', 'Use a boolean state for text, null for other kinds, and the truncation suffix when true'],
        ];

        self::assertSame($expected, array_map(
            static fn(DiagnosticPresentationDiagnostic $case): array => [
                $case->name,
                $case->value,
                $case->title(),
                $case->remediation(),
            ],
            DiagnosticPresentationDiagnostic::cases(),
        ));
    }

    public function testCatalogIdentifiersAreUniqueAndShapeIsExact(): void
    {
        $identifiers = array_map(
            static fn(DiagnosticPresentationDiagnostic $case): string => $case->value,
            DiagnosticPresentationDiagnostic::cases(),
        );
        $reflection = new ReflectionClass(DiagnosticPresentationDiagnostic::class);
        $declaredMethods = array_values(array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $method): bool => !in_array($method->getName(), ['cases', 'from', 'tryFrom'], true),
        ));

        self::assertCount(15, $identifiers);
        self::assertCount(15, array_unique($identifiers));
        foreach ($identifiers as $identifier) {
            self::assertMatchesRegularExpression('/^KGN-PRESENTATION-10(?:0[1-9]|1[0-5])$/D', $identifier);
        }
        self::assertSame(['title', 'remediation'], array_map(
            static fn(ReflectionMethod $method): string => $method->getName(),
            $declaredMethods,
        ));
    }
}
