<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ConsolePresentationBoundaryTest extends TestCase
{
    public function testProductionSourceContainsNoProhibitedRuntimeOrDependencySurface(): void
    {
        $root = dirname(__DIR__, 2) . '/src';
        $sourceFiles = glob($root . '/{,*,*/*}/*.php', GLOB_BRACE);
        self::assertIsArray($sourceFiles);
        $files = 0;
        foreach ($sourceFiles as $sourceFile) {
            ++$files;
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(0, preg_match('/Kongono\\\\(?:ConsoleRuntime|ConsoleBuild|Kernel|DependencyInjection|Composition|Http|Routing|Database|Persistence|Chie|Hantei|Oshie)|Symfony|\$_(?:SERVER|ENV|GET|POST|COOKIE|FILES|REQUEST)|\$argv|getenv\s*\(|\bSTD(?:IN|OUT|ERR)\b|\b(?:exit|die|eval)\s*\(/', $source));
        }
        self::assertSame(16, $files);
    }
}
