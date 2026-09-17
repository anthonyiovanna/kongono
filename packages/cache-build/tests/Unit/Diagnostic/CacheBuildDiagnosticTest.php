<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Diagnostic;

use Kongono\CacheBuild\Diagnostic\CacheBuildDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use PHPUnit\Framework\TestCase;

final class CacheBuildDiagnosticTest extends TestCase
{
    public function testVocabularyIsClosedBoundedAndNondisclosing(): void
    {
        self::assertSame(array_map(static fn(int $number): string => 'KGN-CACHE-BUILD-' . $number, range(2011, 2014)), array_column(CacheBuildDiagnostic::cases(), 'value'));
        foreach (CacheBuildDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            foreach ($diagnostic->contextSchema()->fields() as $field) {
                self::assertContains($field->disclosure(), [DiagnosticDisclosure::Safe, DiagnosticDisclosure::Protected]);
            }
        }
    }
}
