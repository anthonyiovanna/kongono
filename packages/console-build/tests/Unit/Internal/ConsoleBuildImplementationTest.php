<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Tests\Unit\Internal;

use Kongono\ConsoleBuild\Internal\ConsoleBuildImplementation;
use PHPUnit\Framework\TestCase;

final class ConsoleBuildImplementationTest extends TestCase
{
    public function testClosedImplementationListIsExactOrdinalAndSelfAuthenticating(): void
    {
        self::assertCount(16, ConsoleBuildImplementation::BUILDER_PATHS);
        $sorted = ConsoleBuildImplementation::BUILDER_PATHS;
        sort($sorted, SORT_STRING);
        self::assertSame($sorted, ConsoleBuildImplementation::BUILDER_PATHS);
        self::assertContains('src/Internal/ConsoleBuildImplementation.php', $sorted);

        $root = dirname(__DIR__, 3);
        $records = [];
        foreach ($sorted as $path) {
            $bytes = file_get_contents($root . '/' . $path);
            self::assertIsString($bytes);
            $records[] = implode("\t", [$path, (string) strlen($bytes), hash('sha256', $bytes)]);
        }
        self::assertSame(hash('sha256', implode("\n", $records)), ConsoleBuildImplementation::builderSha256());
    }
}
