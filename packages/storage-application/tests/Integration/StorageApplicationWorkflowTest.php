<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Tests\Integration;

use Kongono\StorageApplication\Definition\StorageApplicationDefinition;
use Kongono\StorageApplication\Exception\ExceptionInterface;
use Kongono\StorageApplication\Exception\StorageApplicationWorkflowException;
use Kongono\StorageApplication\Source\StorageProfileSourceLoader;
use Kongono\StorageApplication\Workflow\CurrentStorageApplicationBuild;
use Kongono\StorageApplication\Workflow\StorageApplicationWorkflow;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use PHPUnit\Framework\TestCase;

final class StorageApplicationWorkflowTest extends TestCase
{
    public function testPlanBuildCheckAndCorruptTargetAreExactAndNoClobber(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-storage-workflow-' . bin2hex(random_bytes(8));
        $output = $root . DIRECTORY_SEPARATOR . 'profiles';
        mkdir($root);
        mkdir($output);
        $source = '{"schema":"kongono/storage-application-profile-source/1","profiles":[{"name":"demo","contract_version":1,"namespace":"objects","capabilities":["read"],"digest_algorithm":null,"limits":{"max_chunk_bytes":1024,"max_object_bytes":4096,"max_list_page_size":10}}]}';
        file_put_contents($root . DIRECTORY_SEPARATOR . 'source.json', $source);
        $definition = new StorageApplicationDefinition($root, 'source.json', $output);
        $workflow = new StorageApplicationWorkflow();
        try {
            $plan = $workflow->plan($definition);
            self::assertTrue($plan->publicationRequired());
            self::assertSame([], glob($output . DIRECTORY_SEPARATOR . '*'));
            try {
                $workflow->check($definition);
                self::fail('Missing artifact was accepted.');
            } catch (StorageApplicationWorkflowException) {
            }
            $current = $workflow->build($definition);
            self::assertSame(1, $current->profiles()->profileCount());
            self::assertFalse($workflow->plan($definition)->publicationRequired());
            self::assertTrue($workflow->check($definition)->artifact()->equals($current->artifact()));
            self::assertTrue($workflow->build($definition)->artifact()->equals($current->artifact()));
            $digest = $plan->artifactIdentity();
            $target = $output . '/objects/sha256/' . substr($digest, 0, 2) . '/' . $digest . '/storage-profile.json';
            file_put_contents($target, 'corrupt');
            try {
                $workflow->plan($definition);
                self::fail('Corrupt target was accepted.');
            } catch (StorageApplicationWorkflowException) {
                self::assertSame('corrupt', file_get_contents($target));
            }
        } finally {
            $this->removePrivate($root);
        }
    }

    public function testUnsafeSourceSelectorsAndOutsideOutputHaveNoPublicationEffect(): void
    {
        $parent = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-storage-source-guard-' . bin2hex(random_bytes(8));
        $root = $parent . DIRECTORY_SEPARATOR . 'application';
        $output = $root . DIRECTORY_SEPARATOR . 'profiles';
        $outside = $parent . DIRECTORY_SEPARATOR . 'outside';
        self::assertTrue(mkdir($parent));
        self::assertTrue(mkdir($root));
        self::assertTrue(mkdir($output));
        self::assertTrue(mkdir($outside));
        self::assertTrue(mkdir($root . DIRECTORY_SEPARATOR . 'directory.json'));
        self::assertSame(8, file_put_contents($root . DIRECTORY_SEPARATOR . 'source.json', 'not-json'));
        self::assertTrue(link($root . DIRECTORY_SEPARATOR . 'source.json', $root . DIRECTORY_SEPARATOR . 'hardlink.json'));
        $workflow = new StorageApplicationWorkflow();
        try {
            foreach (['../source.json', '/source.json', 'php://input', 'source\\.json', 'directory.json', 'hardlink.json', 'CON', 'foo//bar', 'source.json:stream'] as $selector) {
                try {
                    $workflow->plan(new StorageApplicationDefinition($root, $selector, $output));
                    self::fail('Unsafe source selector was accepted.');
                } catch (ExceptionInterface) {
                }
            }
            try {
                $workflow->plan(new StorageApplicationDefinition($root, 'source.json', $outside));
                self::fail('Outside output was accepted.');
            } catch (ExceptionInterface) {
            }
            self::assertSame([], array_values(array_diff((array) scandir($output), ['.', '..'])));
            self::assertSame([], array_values(array_diff((array) scandir($outside), ['.', '..'])));
            self::assertSame('not-json', file_get_contents($root . DIRECTORY_SEPARATOR . 'source.json'));
        } finally {
            $this->removePrivate($parent);
        }
    }

    public function testCurrentEvidenceRejectsAnArtifactFromAnotherProfile(): void
    {
        $source = '{"schema":"kongono/storage-application-profile-source/1","profiles":[{"name":"demo","contract_version":1,"namespace":"objects","capabilities":["read"],"digest_algorithm":null,"limits":{"max_chunk_bytes":1024,"max_object_bytes":4096,"max_list_page_size":10}}]}';
        $loader = new StorageProfileSourceLoader();
        $first = $loader->load($source);
        $second = $loader->load(str_replace('"namespace":"objects"', '"namespace":"other-objects"', $source));
        $otherArtifact = (new StorageProfileBuilder())->build($second)->artifact();
        $this->expectException(StorageApplicationWorkflowException::class);
        new CurrentStorageApplicationBuild($first, $otherArtifact);
    }

    private function removePrivate(string $path, ?string $base = null): void
    {
        if (!is_dir($path)) {
            return;
        }
        $resolved = realpath($path);
        self::assertIsString($resolved);
        $base ??= $resolved;
        self::assertTrue($resolved === $base || str_starts_with($resolved, $base . DIRECTORY_SEPARATOR));
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                self::fail('Unexpected private fixture entry.');
            }
            if ($entry->isDir()) {
                $this->removePrivate($entry->getPathname(), $base);
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($path);
    }
}
