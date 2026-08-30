<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Tests\Unit\Diagnostic;

use Kongono\ApplicationGraphArtifact\Diagnostic\ApplicationGraphArtifactDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphArtifactDiagnosticTest extends TestCase
{
    public function testAllocationAndSafeContextAreExact(): void
    {
        self::assertSame(['KGN-GRAPH-1116','KGN-GRAPH-1117','KGN-GRAPH-1118','KGN-GRAPH-1119','KGN-GRAPH-1120','KGN-GRAPH-1121'], array_map(static fn(ApplicationGraphArtifactDiagnostic $d): string => $d->value, ApplicationGraphArtifactDiagnostic::cases()));
        foreach (ApplicationGraphArtifactDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            foreach ($diagnostic->context(['violation' => 'closed_token'])->fields() as $field) {
                self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
            }
        }
    }
}
