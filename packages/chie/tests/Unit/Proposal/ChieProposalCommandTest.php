<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit\Proposal;

use FilesystemIterator;
use Kongono\Chie\Command\ChieCommand;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ChieProposalCommandTest extends TestCase
{
    private const PATH = 'docs/engineering/php-support-policy.md';
    private const BEFORE = "The actively developed line supports exactly one stable PHP minor at a time:\n";
    private const AFTER = "The actively developed line supports one certified stable PHP minor at a time:\n";

    public function testCandidateUsesCanonicalStdoutAndDoesNotChangeTheTarget(): void
    {
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->root());
        $source = $this->source($artifact);
        [$directory, $artifactPath, $specPath] = $this->files($artifact, $this->spec(strtoupper($source['sha'])));
        $beforeIdentity = hash_file('sha256', $this->root() . '/' . self::PATH);
        try {
            [$stdout, $stderr] = $this->streams();
            $exit = (new ChieCommand())->run(['propose', $this->root(), $artifactPath, $specPath], $stdout, $stderr);
            $bytes = $this->contents($stdout);
            $data = $this->decode($bytes);

            self::assertSame(0, $exit);
            self::assertSame('', $this->contents($stderr));
            self::assertSame('candidate', $data['status']);
            self::assertSame('propose', $data['operation']);
            self::assertSame("\n", substr($bytes, -1));
            self::assertStringNotContainsString("\r", $bytes);
            self::assertSame($beforeIdentity, hash_file('sha256', $this->root() . '/' . self::PATH));
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testFailuresAreBoundedAndAValidRequestStillSucceedsAfterEachFailure(): void
    {
        $artifact = (new FrameworkKnowledgeArtifactGenerator())->generate($this->root());
        $source = $this->source($artifact);
        [$directory, $artifactPath, $validSpecPath] = $this->files($artifact, $this->spec(strtoupper($source['sha'])));
        try {
            $invalidSpecPath = $directory . '/invalid.json';
            file_put_contents($invalidSpecPath, "{}\n");
            $staleSpecPath = $directory . '/stale.json';
            file_put_contents($staleSpecPath, $this->spec(str_repeat('A', 64)));
            $missingSpecPath = $directory . '/missing.json';
            $cases = [
                [['propose', $this->root(), $artifactPath, $invalidSpecPath], 64, 'stderr'],
                [['propose', $this->root(), $artifactPath, $staleSpecPath], 65, 'stdout'],
                [['propose', $this->root(), $artifactPath, $missingSpecPath], 66, 'stdout'],
            ];
            foreach ($cases as [$arguments, $expectedExit, $expectedStream]) {
                [$stdout, $stderr] = $this->streams();
                self::assertSame($expectedExit, (new ChieCommand())->run($arguments, $stdout, $stderr));
                $failure = 'stdout' === $expectedStream ? $this->contents($stdout) : $this->contents($stderr);
                self::assertNotSame('', $failure);
                self::assertStringNotContainsString($directory, $failure);
                self::assertStringNotContainsString(self::BEFORE, $failure);

                [$nextOut, $nextErr] = $this->streams();
                self::assertSame(0, (new ChieCommand())->run(['propose', $this->root(), $artifactPath, $validSpecPath], $nextOut, $nextErr));
                self::assertSame('', $this->contents($nextErr));
                self::assertSame('candidate', $this->decode($this->contents($nextOut))['status']);
            }
        } finally {
            $this->removeTree($directory);
        }
    }

    private function root(): string
    {
        return dirname(__DIR__, 5);
    }

    /** @return array{sha: string} */
    private function source(FrameworkKnowledgeArtifact $artifact): array
    {
        foreach ($artifact->sources() as $source) {
            if (self::PATH === $source->path()) {
                return ['sha' => $source->sha256()];
            }
        }
        self::fail('Expected proposal source was absent.');
    }

    private function spec(string $sha): string
    {
        return json_encode([
            'schema' => 'kongono.chie-proposal-request',
            'version' => 1,
            'rule' => 'exact-line-replacement-v1',
            'disclosure' => 'safe',
            'target_path' => self::PATH,
            'base_sha256' => $sha,
            'before' => self::BEFORE,
            'after' => self::AFTER,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }

    /** @return array{string, string, string} */
    private function files(FrameworkKnowledgeArtifact $artifact, string $spec): array
    {
        $directory = str_replace('\\', '/', sys_get_temp_dir() . '/kongono-chie-proposal-command-' . bin2hex(random_bytes(8)));
        mkdir($directory, 0777, true);
        $directory = str_replace('\\', '/', (string) realpath($directory));
        $artifactPath = $directory . '/kongono-framework-knowledge-v1-' . $artifact->artifactSha256() . '.json';
        $specPath = $directory . '/proposal.json';
        file_put_contents($artifactPath, $artifact->canonicalBytes());
        file_put_contents($specPath, $spec);

        return [$directory, $artifactPath, $specPath];
    }

    /** @return array{resource, resource} */
    private function streams(): array
    {
        $stdout = fopen('php://memory', 'w+b');
        $stderr = fopen('php://memory', 'w+b');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        return [$stdout, $stderr];
    }

    /** @param resource $stream */
    private function contents($stream): string
    {
        rewind($stream);

        return (string) stream_get_contents($stream);
    }

    /** @return array<string, mixed> */
    private function decode(string $bytes): array
    {
        $value = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($value);
        self::assertFalse(array_is_list($value));

        $object = [];
        foreach ($value as $key => $item) {
            self::assertIsString($key);
            $object[$key] = $item;
        }

        return $object;
    }

    private function removeTree(string $root): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                self::fail('Temporary cleanup yielded an unexpected iterator value.');
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($root);
    }
}
