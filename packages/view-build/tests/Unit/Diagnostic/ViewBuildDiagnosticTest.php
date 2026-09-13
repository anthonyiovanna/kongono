<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Diagnostic;

use Kongono\ViewBuild\Diagnostic\ViewBuildDiagnostic;
use PHPUnit\Framework\TestCase;

final class ViewBuildDiagnosticTest extends TestCase
{
    public function testDiagnosticVocabularyIsClosedAndStable(): void
    {
        self::assertSame([
            'KGN-VIEW-BUILD-1810',
            'KGN-VIEW-BUILD-1811',
            'KGN-VIEW-BUILD-1812',
            'KGN-VIEW-BUILD-1813',
            'KGN-VIEW-BUILD-1814',
            'KGN-VIEW-BUILD-1815',
        ], array_map(static fn(ViewBuildDiagnostic $diagnostic): string => $diagnostic->value, ViewBuildDiagnostic::cases()));
        foreach (ViewBuildDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $context = $diagnostic->context(['violation' => 'test']);
            self::assertNotSame('', $context->operation());
            self::assertNotSame('', $context->violatedInvariant());
        }
    }
}
