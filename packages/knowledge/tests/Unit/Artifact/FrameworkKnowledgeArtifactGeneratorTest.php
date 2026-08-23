<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Tests\Unit\Artifact;

use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;
use Kongono\Knowledge\Internal\PathGuard;
use Kongono\Knowledge\Source\KnowledgeSourceInventory;
use PHPUnit\Framework\TestCase;

final class FrameworkKnowledgeArtifactGeneratorTest extends TestCase
{
    public function testSameExactInputsProduceByteIdenticalArtifactAndDigest(): void
    {
        $generator = new FrameworkKnowledgeArtifactGenerator();
        $first = $generator->generate($this->frameworkRoot());
        $second = $generator->generate($this->frameworkRoot());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->artifactSha256(), $second->artifactSha256());
        self::assertSame(hash('sha256', $first->canonicalBytes()), $first->artifactSha256());
        self::assertSame(31, $first->sourceCount());
    }

    public function testOneChangedSourceChangesSourceSetAndArtifactIdentity(): void
    {
        $root = $this->copySourceSet();
        try {
            $generator = new FrameworkKnowledgeArtifactGenerator();
            $first = $generator->generate($root);
            file_put_contents($root . '/README.md', file_get_contents($root . '/README.md') . "changed-source\n");
            $second = $generator->generate($root);
            self::assertNotSame($first->sourceSetSha256(), $second->sourceSetSha256());
            self::assertNotSame($first->artifactSha256(), $second->artifactSha256());
            self::assertNotSame($first->canonicalBytes(), $second->canonicalBytes());
        } finally {
            $this->removeTree($root);
        }
    }

    public function testSchemaKeyOrderFinalLfAndReferenceGrammarAreExact(): void
    {
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot());
        $data = json_decode($artifact->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertSame(['schema', 'version', 'scope', 'framework', 'knowledge_contract', 'inventory', 'generator', 'source_state', 'sources'], array_keys($data));
        self::assertSame('kongono.framework-knowledge', $data['schema']);
        self::assertSame(1, $data['version']);
        self::assertStringEndsWith("\n", $artifact->canonicalBytes());
        self::assertFalse(str_ends_with($artifact->canonicalBytes(), "\n\n"));
        foreach ($artifact->sources() as $source) {
            self::assertMatchesRegularExpression('/^kgn-source-v1:[a-f0-9]{64}:\d{4}$/D', $source->reference());
        }
    }

    public function testContentCountsHashesInventoryContractAndGeneratorIdentityAreExact(): void
    {
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot());
        $data = json_decode($artifact->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        $sourceState = $data['source_state'];
        $generator = $data['generator'];
        $contract = $data['knowledge_contract'];
        self::assertIsArray($sourceState);
        self::assertIsArray($generator);
        self::assertIsArray($contract);
        $total = 0;
        foreach ($artifact->sources() as $source) {
            $total += $source->bytes();
            self::assertSame(strlen($source->content()), $source->bytes());
            self::assertSame(hash('sha256', $source->content()), $source->sha256());
            self::assertSame($source, $artifact->sourceByReference($source->reference()));
        }
        self::assertSame($total, $artifact->sourceBytes());
        self::assertSame($artifact->sourceSetSha256(), $sourceState['source_set_sha256']);
        self::assertSame($artifact->generatorImplementationSha256(), $generator['implementation_sha256']);
        self::assertSame($artifact->sources()[10]->sha256(), $contract['sha256']);
        self::assertNull($artifact->sourceByReference('unknown'));
    }

    public function testSourceLimitEncodingPathAliasAndChangedMetadataFailuresAreClosed(): void
    {
        $root = $this->temporaryDirectory();
        try {
            file_put_contents($root . '/bad.txt', "bad\r\n");
            foreach (['../bad.txt', 'bad.txt'] as $path) {
                try {
                    PathGuard::read(PathGuard::resolveRoot($root), $path);
                    self::fail('Rejected source input must fail.');
                } catch (InvalidKnowledgeSourceException $exception) {
                    self::assertStringStartsWith('KGN-KNOWLEDGE-', $exception->diagnosticId());
                }
            }
            $before = ['dev' => 1, 'ino' => 2, 'mode' => 3, 'size' => 4, 'mtime' => 5, 'ctime' => 6];
            $after = $before;
            $after['size'] = 7;
            self::assertFalse(PathGuard::sameMetadata($before, $after));
            self::assertTrue(PathGuard::sameMetadata($before, $before));
        } finally {
            $this->removeTree($root);
        }
    }

    public function testSemanticOutputContainsNoTimeRandomHostEnvironmentNetworkModelCacheOrGitData(): void
    {
        $token = 'KONGONO_FORBIDDEN_' . bin2hex(random_bytes(8));
        putenv('KONGONO_TEST_FORBIDDEN=' . $token);
        try {
            $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot());
            self::assertStringNotContainsString($token, $artifact->canonicalBytes());
            self::assertStringNotContainsString(str_replace('\\', '/', $this->frameworkRoot()), $artifact->canonicalBytes());
            self::assertStringNotContainsString('refs/heads/', $artifact->canonicalBytes());
            $decoded = json_decode($artifact->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($decoded);
            self::assertArrayNotHasKey('timestamp', $decoded);
        } finally {
            putenv('KONGONO_TEST_FORBIDDEN');
        }
    }

    private function frameworkRoot(): string
    {
        return dirname(__DIR__, 5);
    }

    private function copySourceSet(): string
    {
        $target = $this->temporaryDirectory();
        $inventory = KnowledgeSourceInventory::load(dirname(__DIR__, 3));
        foreach ($inventory->definitions() as $definition) {
            $destination = $target . '/' . $definition->path;
            $directory = dirname($destination);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            copy($this->frameworkRoot() . '/' . $definition->path, $destination);
        }

        return $target;
    }

    private function temporaryDirectory(): string
    {
        $path = sys_get_temp_dir() . '/kongono-knowledge-' . bin2hex(random_bytes(8));
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
