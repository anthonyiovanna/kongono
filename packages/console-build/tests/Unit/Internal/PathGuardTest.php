<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Tests\Unit\Internal;

use Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic;
use Kongono\ConsoleBuild\Exception\InvalidConsoleSourceException;
use Kongono\ConsoleBuild\Internal\PathGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PathGuardTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function unsafePaths(): iterable
    {
        yield 'traversal' => ['../kongono.commands.json'];
        yield 'absolute' => ['/kongono.commands.json'];
        yield 'alternate stream' => ['kongono.commands.json:secret'];
        yield 'backslash alias' => ['Application\\kongono.commands.json'];
        yield 'wrong basename' => ['commands.json'];
        yield 'stream wrapper' => ['php://kongono.commands.json'];
    }

    #[DataProvider('unsafePaths')]
    public function testUnsafeSourcePathsAreRejected(string $path): void
    {
        $root = PathGuard::resolveApplicationRoot(dirname(__DIR__, 5));
        $this->expectException(InvalidConsoleSourceException::class);
        PathGuard::readCommandSource($root, $path);
    }

    public function testChangedDuringReadFailsWithDistinctDiagnostic(): void
    {
        $root = sys_get_temp_dir() . '/kongono-console-change-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        $path = $root . '/kongono.commands.json';
        self::assertSame(3, file_put_contents($path, "{}\n"));
        try {
            $resolved = PathGuard::resolveApplicationRoot($root);
            $snapshot = PathGuard::readCommandSource($resolved, 'kongono.commands.json');
            self::assertSame(4, file_put_contents($path, "{ }\n"));
            clearstatcache(true, $path);
            try {
                PathGuard::reverify($snapshot);
                self::fail('Expected changed input rejection.');
            } catch (InvalidConsoleSourceException $failure) {
                self::assertSame(ConsoleBuildDiagnostic::InputChanged->value, $failure->diagnosticId());
            }
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            if (is_dir($root)) {
                rmdir($root);
            }
        }
    }
}
