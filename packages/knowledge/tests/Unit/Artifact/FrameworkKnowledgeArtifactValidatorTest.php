<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Tests\Unit\Artifact;

use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactValidator;
use Kongono\Knowledge\Exception\InvalidKnowledgeArtifactException;
use Kongono\Knowledge\Exception\StaleKnowledgeArtifactException;
use Kongono\Knowledge\Internal\CanonicalJson;
use Kongono\Knowledge\Source\KnowledgeSourceInventory;
use PHPUnit\Framework\TestCase;

final class FrameworkKnowledgeArtifactValidatorTest extends TestCase
{
    public function testValidStructuralAndCurrentValidationReturnsExactArtifact(): void
    {
        $generated = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot());
        $validator = new FrameworkKnowledgeArtifactValidator();
        $structural = $validator->validateStructure($generated->canonicalBytes());
        $current = $validator->validateCurrent($this->frameworkRoot(), $generated->canonicalBytes());
        self::assertSame($generated->canonicalBytes(), $structural->canonicalBytes());
        self::assertSame($generated->artifactSha256(), $structural->artifactSha256());
        self::assertSame($generated->canonicalBytes(), $current->canonicalBytes());
        self::assertSame(31, $current->sourceCount());
    }

    public function testMalformedOverLimitNonCanonicalDuplicateExtraMissingAndReorderedFieldsFail(): void
    {
        $bytes = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot())->canonicalBytes();
        $data = CanonicalJson::decodeObject($bytes, 16);
        $extra = $data;
        $extra['extra'] = true;
        $missing = $data;
        unset($missing['scope']);
        $reordered = $data;
        $schema = $reordered['schema'];
        unset($reordered['schema']);
        $reordered['schema'] = $schema;
        $duplicates = str_replace('{"schema":"kongono.framework-knowledge"', '{"schema":"kongono.framework-knowledge","schema":"kongono.framework-knowledge"', $bytes);
        foreach (['{', ' ' . $bytes, $duplicates, CanonicalJson::encode($extra), CanonicalJson::encode($missing), CanonicalJson::encode($reordered), str_repeat('x', FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES + 1)] as $invalid) {
            $this->expectInvalid($invalid);
        }
    }

    public function testInternalContentCountHashReferenceSourceSetAndContractTamperFails(): void
    {
        $bytes = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot())->canonicalBytes();
        $data = CanonicalJson::decodeObject($bytes, 16);
        $sources = $data['sources'];
        self::assertIsArray($sources);
        $first = $sources[0];
        self::assertIsArray($first);
        self::assertIsString($first['content']);
        self::assertIsInt($first['bytes']);
        foreach (['content', 'bytes', 'sha256', 'reference'] as $field) {
            $tampered = $data;
            $tamperedFirst = $first;
            $tamperedFirst[$field] = match ($field) {
                'content' => $first['content'] . 'x',
                'bytes' => $first['bytes'] + 1,
                'sha256' => str_repeat('0', 64),
                default => 'kgn-source-v1:' . str_repeat('0', 64) . ':0001',
            };
            $tamperedSources = $sources;
            $tamperedSources[0] = $tamperedFirst;
            $tampered['sources'] = $tamperedSources;
            $this->expectInvalid(CanonicalJson::encode($tampered));
        }
        $tampered = $data;
        $state = $tampered['source_state'];
        self::assertIsArray($state);
        $state['source_set_sha256'] = str_repeat('0', 64);
        $tampered['source_state'] = $state;
        $this->expectInvalid(CanonicalJson::encode($tampered));
        $tampered = $data;
        $contract = $tampered['knowledge_contract'];
        self::assertIsArray($contract);
        $contract['sha256'] = str_repeat('0', 64);
        $tampered['knowledge_contract'] = $contract;
        $this->expectInvalid(CanonicalJson::encode($tampered));
    }

    public function testCurrentValidationRejectsInventoryGeneratorContractAndCurrentSourceMismatch(): void
    {
        $validator = new FrameworkKnowledgeArtifactValidator();
        $bytes = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot())->canonicalBytes();
        $data = CanonicalJson::decodeObject($bytes, 16);
        foreach (['inventory', 'generator'] as $field) {
            $tampered = $data;
            $digest = 'inventory' === $field ? 'sha256' : 'implementation_sha256';
            $identity = $tampered[$field];
            self::assertIsArray($identity);
            $identity[$digest] = str_repeat('0', 64);
            $tampered[$field] = $identity;
            $canonical = CanonicalJson::encode($tampered);
            $validator->validateStructure($canonical);
            try {
                $validator->validateCurrent($this->frameworkRoot(), $canonical);
                self::fail('Current identity mismatch must be stale.');
            } catch (StaleKnowledgeArtifactException $exception) {
                self::assertSame('KGN-KNOWLEDGE-1008', $exception->diagnosticId());
            }
        }

        $external = $this->copySourceSet();
        try {
            $externalBytes = (new FrameworkKnowledgeArtifactGenerator())->generate($external)->canonicalBytes();
            file_put_contents($external . '/README.md', file_get_contents($external . '/README.md') . "stale\n");
            $this->expectException(StaleKnowledgeArtifactException::class);
            $validator->validateCurrent($external, $externalBytes);
        } finally {
            $this->removeTree($external);
        }
    }

    public function testMixedVersionAndPartialArtifactsAreRejected(): void
    {
        $data = CanonicalJson::decodeObject((new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot())->canonicalBytes(), 16);
        $version = $data;
        $version['version'] = 2;
        $this->expectInvalid(CanonicalJson::encode($version));
        $scope = $data;
        $scope['scope'] = 'other';
        $this->expectInvalid(CanonicalJson::encode($scope));
        $partial = $data;
        $partialSources = $partial['sources'];
        $partialState = $partial['source_state'];
        self::assertIsArray($partialSources);
        self::assertIsArray($partialState);
        array_pop($partialSources);
        $partialState['source_count'] = 30;
        $partial['sources'] = $partialSources;
        $partial['source_state'] = $partialState;
        $this->expectInvalid(CanonicalJson::encode($partial));
    }

    public function testValidationDoesNotRepairRewriteMutateSourceOrUseStaleFallback(): void
    {
        $path = $this->frameworkRoot() . '/README.md';
        $before = hash_file('sha256', $path);
        $bytes = (new FrameworkKnowledgeArtifactGenerator())->generate($this->frameworkRoot())->canonicalBytes();
        $validator = new FrameworkKnowledgeArtifactValidator();
        $validator->validateStructure($bytes);
        $validator->validateCurrent($this->frameworkRoot(), $bytes);
        self::assertSame($before, hash_file('sha256', $path));
        self::assertSame($bytes, $validator->validateStructure($bytes)->canonicalBytes());
        self::assertFileDoesNotExist($this->frameworkRoot() . '/kongono-framework-knowledge-v1-latest.json');
    }

    private function expectInvalid(string $bytes): void
    {
        try {
            (new FrameworkKnowledgeArtifactValidator())->validateStructure($bytes);
            self::fail('Invalid artifact must fail.');
        } catch (InvalidKnowledgeArtifactException $exception) {
            self::assertSame('KGN-KNOWLEDGE-1007', $exception->diagnosticId());
        }
    }

    private function frameworkRoot(): string
    {
        return dirname(__DIR__, 5);
    }

    private function copySourceSet(): string
    {
        $target = sys_get_temp_dir() . '/kongono-knowledge-validator-' . bin2hex(random_bytes(8));
        mkdir($target, 0777, true);
        foreach (KnowledgeSourceInventory::load(dirname(__DIR__, 3))->definitions() as $definition) {
            $destination = $target . '/' . $definition->path;
            if (!is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0777, true);
            }
            copy($this->frameworkRoot() . '/' . $definition->path, $destination);
        }

        return str_replace('\\', '/', $target);
    }

    private function removeTree(string $path): void
    {
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
