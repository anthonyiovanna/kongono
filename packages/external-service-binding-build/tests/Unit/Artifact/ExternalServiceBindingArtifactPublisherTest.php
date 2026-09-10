<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Artifact;

use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifact;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactPublisher;
use Kongono\ExternalServiceBindingBuild\Exception\ExternalServiceBindingArtifactPublicationException;
use Kongono\ExternalServiceBindingBuild\Tests\Support\ExternalServiceBindingSourceFactory;
use PHPUnit\Framework\TestCase;

final class ExternalServiceBindingArtifactPublisherTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/kongono-binding-artifact-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory, 0700));
        $resolved = realpath($this->directory);
        self::assertNotFalse($resolved);
        $this->directory = str_replace('\\', '/', $resolved);
    }

    protected function tearDown(): void
    {
        $resolved = realpath($this->directory);
        if (false === $resolved || str_replace('\\', '/', $resolved) !== $this->directory || !str_starts_with($this->directory, str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-binding-artifact-')) {
            return;
        }
        $paths = glob($this->directory . '/*');
        if (is_array($paths)) {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }
        @rmdir($this->directory);
    }

    public function testPublicationIsCompleteContentAddressedIdempotentAndResidueFree(): void
    {
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build(ExternalServiceBindingSourceFactory::create());
        $publisher = new ExternalServiceBindingArtifactPublisher();
        $path = $publisher->publish($artifact, $this->directory);
        self::assertSame($this->directory . '/kongono-external-service-bindings-v1-' . $artifact->artifactSha256() . '.json', $path);
        self::assertSame($artifact->canonicalBytes(), file_get_contents($path));
        self::assertSame($path, $publisher->publish($artifact, $this->directory));
        self::assertSame([$path], glob($this->directory . '/*'));
    }

    public function testConflictAndUnsafePathsFailWithoutClobber(): void
    {
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build(ExternalServiceBindingSourceFactory::create());
        $target = $this->directory . '/kongono-external-service-bindings-v1-' . $artifact->artifactSha256() . '.json';
        file_put_contents($target, 'conflict');
        try {
            (new ExternalServiceBindingArtifactPublisher())->publish($artifact, $this->directory);
            self::fail('Expected conflict rejection.');
        } catch (ExternalServiceBindingArtifactPublicationException) {
            self::assertSame('conflict', file_get_contents($target));
        }
        foreach (['', '.', 'relative/path', dirname($this->directory) . '/missing', $this->directory . "\0suffix"] as $directory) {
            try {
                (new ExternalServiceBindingArtifactPublisher())->publish($artifact, $directory);
                self::fail('Expected unsafe path rejection.');
            } catch (ExternalServiceBindingArtifactPublicationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testCompleteArtifactValueIsValidatedBeforeWriting(): void
    {
        $builder = new ExternalServiceBindingArtifactBuilder();
        $artifact = $builder->build(ExternalServiceBindingSourceFactory::create());
        $invalid = new ExternalServiceBindingArtifact(
            $artifact->canonicalBytes(),
            $artifact->artifactSha256(),
            $artifact->sourceSha256(),
            ExternalServiceBindingSourceFactory::create('other.bindings'),
        );

        try {
            (new ExternalServiceBindingArtifactPublisher())->publish($invalid, $this->directory);
            self::fail('Expected complete artifact value rejection.');
        } catch (ExternalServiceBindingArtifactPublicationException) {
            self::assertSame([], glob($this->directory . '/*'));
        }
    }

    public function testLinkAndWindowsJunctionOutputDirectoriesAreRejected(): void
    {
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build(ExternalServiceBindingSourceFactory::create());
        $real = $this->directory . '/real';
        self::assertTrue(mkdir($real, 0700));
        $link = $this->directory . '/link';
        if (@symlink($real, $link)) {
            try {
                (new ExternalServiceBindingArtifactPublisher())->publish($artifact, $link);
                self::fail('Expected link path rejection.');
            } catch (ExternalServiceBindingArtifactPublicationException) {
                self::addToAssertionCount(1);
            } finally {
                self::assertTrue(unlink($link));
            }
        } elseif ('Windows' !== PHP_OS_FAMILY) {
            self::markTestSkipped('Symbolic-link creation is unavailable in this environment.');
        }

        if ('Windows' === PHP_OS_FAMILY) {
            $junction = $this->directory . '/junction';
            $command = sprintf('mklink /J "%s" "%s"', str_replace('/', '\\', $junction), str_replace('/', '\\', $real));
            $output = [];
            $exitCode = -1;
            exec('cmd.exe /d /c ' . $command, $output, $exitCode);
            self::assertSame(0, $exitCode, implode("\n", $output));
            try {
                (new ExternalServiceBindingArtifactPublisher())->publish($artifact, str_replace('\\', '/', $junction));
                self::fail('Expected junction path rejection.');
            } catch (ExternalServiceBindingArtifactPublicationException) {
                self::addToAssertionCount(1);
            } finally {
                self::assertTrue(rmdir($junction));
            }
        }
        self::assertTrue(rmdir($real));
    }
}
