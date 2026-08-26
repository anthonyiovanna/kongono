<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Internal;

use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\PathGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PathGuardTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function unsafePaths(): iterable
    {
        yield 'traversal' => ['../kongono.persistence.json'];
        yield 'absolute' => ['/kongono.persistence.json'];
        yield 'alternate stream' => ['kongono.persistence.json:secret'];
        yield 'backslash alias' => ['Application\\kongono.persistence.json'];
    }

    #[DataProvider('unsafePaths')]
    public function testUnsafeSourcePathsAreRejected(string $path): void
    {
        $root = PathGuard::resolveApplicationRoot(dirname(__DIR__, 5));
        $this->expectException(InvalidPersistenceSourceException::class);
        PathGuard::readPersistenceSource($root, $path);
    }

    public function testChangedDuringReadFailsWithDistinctDiagnostic(): void
    {
        $temporaryRoot = sys_get_temp_dir() . '/kongono-persistence-change-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($temporaryRoot));
        $path = $temporaryRoot . '/evidence.json';
        self::assertSame(8, file_put_contents($path, 'evidence'));
        try {
            $root = PathGuard::resolveApplicationRoot($temporaryRoot);
            $snapshot = PathGuard::readProjectFile($root, 'evidence.json');
            self::assertSame(7, file_put_contents($path, 'changed'));
            try {
                PathGuard::reverify($snapshot);
                self::fail('Expected changed input rejection.');
            } catch (InvalidPersistenceSourceException $failure) {
                self::assertSame(PersistenceBuildDiagnostic::InputChanged->value, $failure->diagnosticId());
            }
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
            if (is_dir($temporaryRoot)) {
                rmdir($temporaryRoot);
            }
        }
    }
}
