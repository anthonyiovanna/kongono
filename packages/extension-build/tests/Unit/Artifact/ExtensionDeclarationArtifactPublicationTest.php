<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Tests\Unit\Artifact;

use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifact;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactPublisher;
use Kongono\ExtensionBuild\Exception\ExtensionDeclarationPublicationException;
use PHPUnit\Framework\TestCase;

final class ExtensionDeclarationArtifactPublicationTest extends TestCase
{
    public function testPublicationIsContentAddressedAtomicIdempotentAndCleanupSafe(): void
    {
        $directory = self::temporaryDirectory();
        try {
            $artifact = (new ExtensionDeclarationArtifactBuilder())->build([]);
            $publisher = new ExtensionDeclarationArtifactPublisher();
            $target = $publisher->publish($artifact, $directory);

            self::assertSame($directory . '/kongono-extension-declarations-v1-' . $artifact->artifactSha256() . '.json', $target);
            self::assertSame($artifact->canonicalBytes(), file_get_contents($target));
            self::assertSame($target, $publisher->publish($artifact, $directory));
            $temporaryFiles = glob($directory . '/.kongono-extension-declarations-*');
            self::assertSame([], false === $temporaryFiles ? [] : $temporaryFiles);
        } finally {
            self::removeDirectory($directory);
        }
    }

    public function testConflictingTargetAndStoredIdentityMismatchNeverClobber(): void
    {
        $directory = self::temporaryDirectory();
        try {
            $artifact = (new ExtensionDeclarationArtifactBuilder())->build([]);
            $target = $directory . '/kongono-extension-declarations-v1-' . $artifact->artifactSha256() . '.json';
            file_put_contents($target, 'conflict');
            try {
                (new ExtensionDeclarationArtifactPublisher())->publish($artifact, $directory);
                self::fail('Conflicting target was overwritten.');
            } catch (ExtensionDeclarationPublicationException $exception) {
                self::assertSame('KGN-EXTENSION-1211', $exception->diagnosticId());
                self::assertSame('conflict', file_get_contents($target));
            }

            unlink($target);
            $mismatched = new ExtensionDeclarationArtifact(
                $artifact->canonicalBytes(),
                str_repeat('0', 64),
                $artifact->declarationSetIdentity(),
                $artifact->declarationSetSha256(),
                $artifact->declarationCount(),
            );
            $this->expectException(ExtensionDeclarationPublicationException::class);
            (new ExtensionDeclarationArtifactPublisher())->publish($mismatched, $directory);
        } finally {
            self::removeDirectory($directory);
        }
    }

    public function testRelativeAmbiguousAndSymlinkPathsAreRejected(): void
    {
        $directory = self::temporaryDirectory();
        $alias = $directory . '-alias';
        try {
            $artifact = (new ExtensionDeclarationArtifactBuilder())->build([]);
            foreach (['relative/path', $directory . '/.'] as $rejected) {
                try {
                    (new ExtensionDeclarationArtifactPublisher())->publish($artifact, $rejected);
                    self::fail('Unsafe publication path was accepted.');
                } catch (ExtensionDeclarationPublicationException $exception) {
                    self::assertSame('KGN-EXTENSION-1211', $exception->diagnosticId());
                }
            }
            if (@symlink($directory, $alias)) {
                try {
                    (new ExtensionDeclarationArtifactPublisher())->publish($artifact, str_replace('\\', '/', $alias));
                    self::fail('Symlink publication directory was accepted.');
                } catch (ExtensionDeclarationPublicationException $exception) {
                    self::assertSame('KGN-EXTENSION-1211', $exception->diagnosticId());
                }
            }
        } finally {
            if (is_link($alias)) {
                unlink($alias);
            }
            self::removeDirectory($directory);
        }
    }

    public function testWindowsJunctionAncestorIsRejectedWhenSupported(): void
    {
        if ('Windows' !== PHP_OS_FAMILY) {
            self::markTestSkipped('Windows junction proof is Windows-specific.');
        }
        $base = self::temporaryDirectory();
        $real = $base . '/real';
        $junction = $base . '/junction';
        mkdir($real);
        try {
            $junctionCommand = sprintf(
                'mklink /J "%s" "%s"',
                str_replace('/', '\\', $junction),
                str_replace('/', '\\', $real),
            );
            $output = [];
            $exitCode = -1;
            exec('cmd.exe /d /c ' . $junctionCommand, $output, $exitCode);
            if (0 !== $exitCode) {
                self::markTestSkipped('Junction creation is unavailable.');
            }

            $this->expectException(ExtensionDeclarationPublicationException::class);
            (new ExtensionDeclarationArtifactPublisher())->publish(
                (new ExtensionDeclarationArtifactBuilder())->build([]),
                str_replace('\\', '/', $junction),
            );
        } finally {
            if (is_dir($junction) && realpath($junction) !== $junction) {
                rmdir($junction);
            }
            self::removeDirectory($base);
        }
    }

    private static function temporaryDirectory(): string
    {
        $directory = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-extension-build-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory));
        $resolved = realpath($directory);
        self::assertIsString($resolved);

        return str_replace('\\', '/', $resolved);
    }

    private static function removeDirectory(string $directory): void
    {
        $resolved = realpath($directory);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $directory || !str_contains($directory, '/kongono-extension-build-')) {
            self::fail('Temporary cleanup target is unsafe.');
        }
        $paths = glob($directory . '/*');
        foreach (array_reverse(false === $paths ? [] : $paths) as $path) {
            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                self::removeDirectory(str_replace('\\', '/', $path));
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}
