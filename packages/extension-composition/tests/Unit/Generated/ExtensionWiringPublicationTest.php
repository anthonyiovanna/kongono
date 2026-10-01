<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Tests\Unit\Generated;

use Kongono\ExtensionAdmission\Admission\ExtensionAdmissionBuilder;
use Kongono\ExtensionAdmission\Selection\ExtensionAdmissionPlan;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionComposition\Exception\ExtensionWiringPublicationException;
use Kongono\ExtensionComposition\Generated\ExtensionWiringGenerator;
use Kongono\ExtensionComposition\Generated\ExtensionWiringPublisher;
use Kongono\ExtensionComposition\Plan\ExtensionGrantPlan;
use PHPUnit\Framework\TestCase;

final class ExtensionWiringPublicationTest extends TestCase
{
    public function testAtomicPublicationIsContentAddressedIdempotentAndNoClobber(): void
    {
        $directory = self::temporaryDirectory();
        try {
            $artifact = self::artifact();
            $publisher = new ExtensionWiringPublisher();
            $path = $publisher->publish($artifact, $directory);
            self::assertSame(str_replace('\\', '/', $directory) . '/' . $artifact->wiringSha256() . '.php', $path);
            self::assertSame($artifact->canonicalBytes(), file_get_contents($path));
            self::assertSame($path, $publisher->publish($artifact, $directory));
            self::assertSame([], self::temporaryFiles($directory));
            self::assertTrue(unlink($path));
            self::assertSame(1, file_put_contents($path, 'x'));
            try {
                $publisher->publish($artifact, $directory);
                self::fail('Conflicting output was clobbered.');
            } catch (ExtensionWiringPublicationException) {
                self::assertSame('x', file_get_contents($path));
            }
        } finally {
            self::removeDirectory($directory);
        }
    }

    public function testStoredIdentityMismatchFailsBeforePublication(): void
    {
        $artifact = self::artifact();
        $mismatched = new \Kongono\ExtensionComposition\Artifact\GeneratedExtensionWiringArtifact(
            $artifact->canonicalBytes(),
            str_repeat('0', 64),
            $artifact->declarationSetIdentity(),
            $artifact->admissionIdentity(),
            $artifact->grantPlanIdentity(),
            $artifact->generatorImplementationSha256(),
            $artifact->generatedNamespace(),
            $artifact->applicationClass(),
            $artifact->operationClass(),
            $artifact->implementationCount(),
            $artifact->decisionCount(),
            $artifact->applicationProviderCount(),
            $artifact->operationProviderCount(),
        );
        $directory = self::temporaryDirectory();
        try {
            $this->expectException(ExtensionWiringPublicationException::class);
            (new ExtensionWiringPublisher())->publish($mismatched, $directory);
        } finally {
            self::removeDirectory($directory);
        }
    }

    public function testTraversalStreamAndMissingDirectoriesFailWithoutPathDisclosure(): void
    {
        foreach (['', '../private', 'C:\\private:stream', sys_get_temp_dir() . '/missing-' . bin2hex(random_bytes(4))] as $path) {
            try {
                (new ExtensionWiringPublisher())->publish(self::artifact(), $path);
                self::fail('Unsafe path was accepted.');
            } catch (ExtensionWiringPublicationException $exception) {
                if ('' !== $path) {
                    self::assertStringNotContainsString($path, (string) $exception);
                }
            }
        }
    }

    public function testAmbiguousAndSymlinkPublicationDirectoriesAreRejected(): void
    {
        $directory = self::temporaryDirectory();
        $alias = $directory . '-alias';
        try {
            foreach ([$directory . '/.', $directory . '/../' . basename($directory)] as $path) {
                try {
                    (new ExtensionWiringPublisher())->publish(self::artifact(), $path);
                    self::fail('Ambiguous publication path was accepted.');
                } catch (ExtensionWiringPublicationException) {
                    self::assertSame([], self::temporaryFiles($directory));
                }
            }
            if (@symlink($directory, $alias)) {
                try {
                    (new ExtensionWiringPublisher())->publish(self::artifact(), str_replace('\\', '/', $alias));
                    self::fail('Symlink publication directory was accepted.');
                } catch (ExtensionWiringPublicationException) {
                    self::assertSame([], self::temporaryFiles($directory));
                }
            }
        } finally {
            if (is_link($alias)) {
                $removed = 'Windows' === PHP_OS_FAMILY ? rmdir($alias) : unlink($alias);
                self::assertTrue($removed);
            }
            clearstatcache(true, $alias);
            self::assertFalse(is_link($alias));
            self::assertFalse(file_exists($alias));
            self::removeDirectory($directory);
            clearstatcache(true, $directory);
            self::assertFalse(file_exists($directory));
        }
    }

    public function testWindowsJunctionPublicationDirectoryIsRejectedWhenSupported(): void
    {
        if ('Windows' !== PHP_OS_FAMILY) {
            self::markTestSkipped('Windows junction proof is Windows-specific.');
        }
        $base = self::temporaryDirectory();
        $real = $base . '/real';
        $junction = $base . '/junction';
        self::assertTrue(mkdir($real));
        try {
            $command = sprintf('mklink /J "%s" "%s"', str_replace('/', '\\', $junction), str_replace('/', '\\', $real));
            $output = [];
            $exitCode = -1;
            exec('cmd.exe /d /c ' . $command, $output, $exitCode);
            if (0 !== $exitCode) {
                self::markTestSkipped('Junction creation is unavailable.');
            }
            $this->expectException(ExtensionWiringPublicationException::class);
            (new ExtensionWiringPublisher())->publish(self::artifact(), str_replace('\\', '/', $junction));
        } finally {
            if (false !== @lstat($junction)) {
                self::assertTrue(rmdir($junction));
            }
            self::removeDirectory($base);
        }
    }

    private static function artifact(): \Kongono\ExtensionComposition\Artifact\GeneratedExtensionWiringArtifact
    {
        $declarations = [];
        $identity = (new ExtensionDeclarationArtifactBuilder())->build($declarations)->declarationSetIdentity();
        $admission = (new ExtensionAdmissionBuilder())->build($declarations, new ExtensionAdmissionPlan($identity, [], []), []);
        return (new ExtensionWiringGenerator())->generate($declarations, $admission, new ExtensionGrantPlan($admission->admissionIdentity(), [], []));
    }

    private static function temporaryDirectory(): string
    {
        $directory = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-extension-wiring-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory));
        $resolved = realpath($directory);
        self::assertIsString($resolved);

        return str_replace('\\', '/', $resolved);
    }

    /** @return list<string> */
    private static function temporaryFiles(string $directory): array
    {
        $paths = glob($directory . '/.kongono-extension-wiring-*');

        return false === $paths ? [] : $paths;
    }

    private static function removeDirectory(string $directory): void
    {
        $resolved = realpath($directory);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $directory || !str_contains($directory, '/kongono-extension-wiring-')) {
            self::fail('Temporary cleanup target is unsafe.');
        }
        $paths = glob($directory . '/*');
        foreach (array_reverse(false === $paths ? [] : $paths) as $path) {
            if ('Windows' === PHP_OS_FAMILY && false !== @lstat($path) && !is_file($path) && !is_dir($path)) {
                self::assertTrue(rmdir($path));
            } elseif (is_link($path)) {
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
