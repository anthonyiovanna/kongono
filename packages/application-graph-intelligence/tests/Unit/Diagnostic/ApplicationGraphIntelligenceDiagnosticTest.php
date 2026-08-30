<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Tests\Unit\Diagnostic;

use Kongono\ApplicationGraphIntelligence\Diagnostic\ApplicationGraphIntelligenceDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphIntelligenceDiagnosticTest extends TestCase
{
    public function testCatalogHasExactIdentifiersAndOneFixedSafeField(): void
    {
        self::assertSame([
            ['InvalidDerivedValue', 'KGN-APPLICATION-GRAPH-INTELLIGENCE-1001'],
            ['InvalidVerificationValue', 'KGN-APPLICATION-GRAPH-INTELLIGENCE-1002'],
        ], array_map(static fn(ApplicationGraphIntelligenceDiagnostic $diagnostic): array => [$diagnostic->name, $diagnostic->value], ApplicationGraphIntelligenceDiagnostic::cases()));

        foreach (ApplicationGraphIntelligenceDiagnostic::cases() as $diagnostic) {
            $fields = $diagnostic->contextSchema()->fields();
            self::assertCount(1, $fields);
            self::assertSame('component', $fields[0]->key());
            self::assertSame(DiagnosticDisclosure::Safe, $fields[0]->disclosure());
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
        }
    }
}
