<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit\Command;

use Kongono\Chie\Command\ChieCommand;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use PHPUnit\Framework\TestCase;

final class ChieCommandTest extends TestCase
{
    public function testSuccessfulExplanationUsesOnlyStdoutAndExitZero(): void
    {
        [$directory, $path] = $this->artifactFile($this->artifact());
        try {
            [$stdout, $stderr] = $this->streams();
            $exit = (new ChieCommand())->run(['explain', $this->root(), $path, 'developer-intelligence'], $stdout, $stderr);

            self::assertSame(0, $exit);
            self::assertSame('', $this->contents($stderr));
            self::assertSame('answered', $this->decode($this->contents($stdout))['status']);
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testUnsupportedTopicUsesCanonicalStdoutAndExitSixtyFive(): void
    {
        [$directory, $path] = $this->artifactFile($this->artifact());
        try {
            [$stdout, $stderr] = $this->streams();
            $exit = (new ChieCommand())->run(['explain', $this->root(), $path, 'unknown-topic'], $stdout, $stderr);

            self::assertSame(65, $exit);
            self::assertSame('', $this->contents($stderr));
            $data = $this->decode($this->contents($stdout));
            self::assertSame('unsupported', $data['status']);
            self::assertSame(['topic-unsupported'], $data['uncertainties']);
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testStaleArtifactIsVisibleWithoutPathDisclosure(): void
    {
        $temporaryRoot = $this->copyArtifactSources($this->artifact());
        try {
            $readme = $temporaryRoot . '/README.md';
            file_put_contents($readme, str_replace('Kongono', 'KongonO', (string) file_get_contents($readme)));
            [$artifactDirectory, $path] = $this->artifactFile((new FrameworkKnowledgeArtifactGenerator())->generate($temporaryRoot));
            try {
                [$stdout, $stderr] = $this->streams();
                $exit = (new ChieCommand())->run(['explain', $this->root(), $path, 'php-support'], $stdout, $stderr);
                self::assertSame(65, $exit);
                self::assertSame('', $this->contents($stderr));
                self::assertSame('stale', $this->decode($this->contents($stdout))['status']);
                self::assertStringNotContainsString($path, $this->contents($stdout));
            } finally {
                $this->removeTree($artifactDirectory);
            }
        } finally {
            $this->removeTree($temporaryRoot);
        }
    }

    public function testUnavailableArtifactUsesStdoutAndExitSixtySix(): void
    {
        $directory = $this->temporaryDirectory();
        $path = $directory . '/kongono-framework-knowledge-v1-' . str_repeat('0', 64) . '.json';
        try {
            [$stdout, $stderr] = $this->streams();
            $exit = (new ChieCommand())->run(['explain', $this->root(), $path, 'php-support'], $stdout, $stderr);

            self::assertSame(66, $exit);
            self::assertSame('', $this->contents($stderr));
            self::assertSame('unavailable', $this->decode($this->contents($stdout))['status']);
            self::assertStringNotContainsString($path, $this->contents($stdout));
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testInvalidUsageUsesOnlySafeStderrAndDoesNotEchoInput(): void
    {
        [$stdout, $stderr] = $this->streams();
        $rejected = 'Secret Token Value';
        $exit = (new ChieCommand())->run(['explain', $this->root(), 'artifact', $rejected], $stdout, $stderr);

        self::assertSame(64, $exit);
        self::assertSame('', $this->contents($stdout));
        self::assertStringContainsString('KGN-CHIE-1001', $this->contents($stderr));
        self::assertStringNotContainsString($rejected, $this->contents($stderr));
        self::assertSame("\n", substr($this->contents($stderr), -1));
    }

    public function testUnexpectedOutputFailureUsesFixedInternalErrorContract(): void
    {
        [$directory, $path] = $this->artifactFile($this->artifact());
        try {
            $stdout = fopen('php://memory', 'w+b');
            $stderr = fopen('php://memory', 'w+b');
            self::assertIsResource($stdout);
            self::assertIsResource($stderr);
            fclose($stdout);
            $exit = (new ChieCommand())->run(['explain', $this->root(), $path, 'php-support'], $stdout, $stderr);

            self::assertSame(70, $exit);
            self::assertStringContainsString('KGN-CHIE-1010', $this->contents($stderr));
            self::assertStringNotContainsString($path, $this->contents($stderr));
        } finally {
            $this->removeTree($directory);
        }
    }

    private function root(): string
    {
        return dirname(__DIR__, 5);
    }

    private function artifact(): FrameworkKnowledgeArtifact
    {
        return (new FrameworkKnowledgeArtifactGenerator())->generate($this->root());
    }

    /** @return array{string, string} */
    private function artifactFile(FrameworkKnowledgeArtifact $artifact): array
    {
        $directory = $this->temporaryDirectory();
        $path = $directory . '/kongono-framework-knowledge-v1-' . $artifact->artifactSha256() . '.json';
        file_put_contents($path, $artifact->canonicalBytes());

        return [$directory, str_replace('\\', '/', (string) realpath($path))];
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

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/kongono-chie-command-' . bin2hex(random_bytes(8));
        mkdir($directory, 0777, true);

        return str_replace('\\', '/', (string) realpath($directory));
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

    private function copyArtifactSources(FrameworkKnowledgeArtifact $artifact): string
    {
        $root = $this->temporaryDirectory();
        foreach ($artifact->sources() as $source) {
            $path = $root . '/' . $source->path();
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, $source->content());
        }

        return $root;
    }

    private function removeTree(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                self::fail('Temporary cleanup yielded an unexpected iterator value.');
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($root);
    }
}
