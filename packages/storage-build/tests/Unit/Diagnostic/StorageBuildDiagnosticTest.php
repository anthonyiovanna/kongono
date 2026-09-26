<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Diagnostic;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\StorageBuild\Diagnostic\StorageBuildDiagnostic;
use PHPUnit\Framework\TestCase;

final class StorageBuildDiagnosticTest extends TestCase
{
    public function testVocabularyIsClosedBoundedAndNondisclosing(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-STORAGE-BUILD-' . $number, range(2201, 2204)), array_column(StorageBuildDiagnostic::cases(), 'value'));
        foreach (StorageBuildDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            foreach ($diagnostic->contextSchema()->fields() as $field) {
                self::assertContains($field->disclosure(), [DiagnosticDisclosure::Safe, DiagnosticDisclosure::Protected]);
            }
        }
    }
}
