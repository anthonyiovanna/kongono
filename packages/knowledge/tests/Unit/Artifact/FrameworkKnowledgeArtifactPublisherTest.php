<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Tests\Unit\Artifact;

use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactPublisher;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactValidator;
use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use Kongono\Knowledge\Exception\KnowledgePublicationException;
use PHPUnit\Framework\TestCase;

final class FrameworkKnowledgeArtifactPublisherTest extends TestCase
{
    public function testContentAddressedNameAndPublishedBytesAreExact(): void
    {
        $directory = $this->temporaryDirectory();
        try {
            $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot());
            $basename = (new FrameworkKnowledgeArtifactPublisher())->publish($artifact, $directory);
            self::assertSame('kongono-framework-knowledge-v1-' . $artifact->artifactSha256() . '.json', $basename);
            self::assertSame($artifact->canonicalBytes(), file_get_contents($directory . '/' . $basename));
            self::assertSame($artifact->artifactSha256(), hash_file('sha256', $directory . '/' . $basename));
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testInitialPublicationIsAtomicNoClobberAndLeavesNoTemporaryFile(): void
    {
        $directory = $this->temporaryDirectory();
        try {
            $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot());
            $basename = (new FrameworkKnowledgeArtifactPublisher())->publish($artifact, $directory);
            $entries = array_values(array_diff(scandir($directory), ['.', '..']));
            self::assertSame([$basename], $entries);
            self::assertFileExists($directory . '/' . $basename);
            self::assertFalse(is_link($directory . '/' . $basename));
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testIdenticalExistingTargetIsIdempotentOnlyAfterValidation(): void
    {
        $directory = $this->temporaryDirectory();
        try {
            $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot());
            $publisher = new FrameworkKnowledgeArtifactPublisher();
            $first = $publisher->publish($artifact, $directory);
            $before = hash_file('sha256', $directory . '/' . $first);
            $second = $publisher->publish($artifact, $directory);
            self::assertSame($first, $second);
            self::assertSame($before, hash_file('sha256', $directory . '/' . $second));
            self::assertSame($artifact->canonicalBytes(), (new FrameworkKnowledgeArtifactValidator())->validateStructure((string) file_get_contents($directory . '/' . $second))->canonicalBytes());
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testConflictingExistingTargetIsRejectedWithoutOverwrite(): void
    {
        $directory = $this->temporaryDirectory();
        try {
            $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot());
            $target = $directory . '/kongono-framework-knowledge-v1-' . $artifact->artifactSha256() . '.json';
            file_put_contents($target, "conflict\n");
            try {
                (new FrameworkKnowledgeArtifactPublisher())->publish($artifact, $directory);
                self::fail('Conflicting target must fail.');
            } catch (KnowledgePublicationException $exception) {
                self::assertSame('target_conflict', $exception->diagnosticContext()->fields()[0]->value());
            }
            self::assertSame("conflict\n", file_get_contents($target));
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testMissingUnsafeOutputAndClosedWriteFailureTokensRemainExplicit(): void
    {
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot());
        try {
            (new FrameworkKnowledgeArtifactPublisher())->publish($artifact, sys_get_temp_dir() . '/missing-' . bin2hex(random_bytes(8)));
            self::fail('Missing output directory must fail.');
        } catch (KnowledgePublicationException $exception) {
            self::assertSame('output_directory', $exception->diagnosticContext()->fields()[0]->value());
        }
        foreach (['temporary_create', 'temporary_write', 'temporary_flush', 'temporary_sync', 'atomic_link', 'temporary_cleanup'] as $token) {
            $exception = new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, $token);
            self::assertSame($token, $exception->diagnosticContext()->fields()[0]->value());
        }
    }

    public function testPublicationCreatesNoOverwritePointerCacheOrRepositoryWrite(): void
    {
        $directory = $this->temporaryDirectory();
        try {
            $repositoryHash = hash_file('sha256', $this->frameworkRoot() . '/README.md');
            $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot());
            (new FrameworkKnowledgeArtifactPublisher())->publish($artifact, $directory);
            self::assertFileDoesNotExist($directory . '/latest');
            self::assertFileDoesNotExist($directory . '/latest.json');
            self::assertFileDoesNotExist($this->frameworkRoot() . '/kongono-framework-knowledge-v1-' . $artifact->artifactSha256() . '.json');
            self::assertSame($repositoryHash, hash_file('sha256', $this->frameworkRoot() . '/README.md'));
        } finally {
            $this->removeTree($directory);
        }
    }

    private function frameworkRoot(): string
    {
        return dirname(__DIR__, 5);
    }

    private function temporaryDirectory(): string
    {
        $path = sys_get_temp_dir() . '/kongono-knowledge-publisher-' . bin2hex(random_bytes(8));
        mkdir($path, 0777, true);

        return str_replace('\\', '/', $path);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                throw new \RuntimeException('Unexpected temporary directory entry.');
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
