<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ExtensionInspectionAdversarialTest extends TestCase
{
    public function testLoaderNeverLoadsOrReflectsGeneratedPhp(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Loading/ExtensionInspectionLoader.php');
        self::assertDoesNotMatchRegularExpression('/\b(require|include|eval|Reflection)\b/', $source);
        self::assertStringContainsString('validateStructure', $source);
        self::assertStringNotContainsString('validateCurrent', $source);
    }
}
