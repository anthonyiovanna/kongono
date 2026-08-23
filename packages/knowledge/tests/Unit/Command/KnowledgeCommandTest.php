<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Tests\Unit\Command;

use Kongono\Knowledge\Command\KnowledgeCommand;
use PHPUnit\Framework\TestCase;

final class KnowledgeCommandTest extends TestCase
{
    public function testExactThreeCommandShapesAndUsageExitCodesAreEnforced(): void
    {
        foreach ([[], ['unknown'], ['check'], ['generate', $this->frameworkRoot()], ['validate', $this->frameworkRoot()]] as $arguments) {
            [$exit, $stdout, $stderr] = $this->executeCommand($arguments);
            self::assertSame(64, $exit);
            self::assertSame('', $stdout);
            self::assertStringEndsWith("\n", $stderr);
            $failure = json_decode($stderr, true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($failure);
            self::assertSame('KGN-KNOWLEDGE-1010', $failure['diagnostic_id']);
        }
    }

    public function testSuccessAndFailureStreamsUseExactJsonKeyOrderAndFinalLf(): void
    {
        [$exit, $stdout, $stderr] = $this->executeCommand(['check', $this->frameworkRoot()]);
        self::assertSame(0, $exit);
        self::assertSame('', $stderr);
        self::assertStringEndsWith("\n", $stdout);
        self::assertFalse(str_ends_with($stdout, "\n\n"));
        $success = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($success);
        self::assertSame(['status', 'operation', 'artifact_sha256', 'source_set_sha256', 'source_count'], array_keys($success));

        [$failureExit, $failureOut, $failureErr] = $this->executeCommand(['check', $this->frameworkRoot(), 'extra']);
        self::assertSame(64, $failureExit);
        self::assertSame('', $failureOut);
        $failure = json_decode($failureErr, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($failure);
        self::assertSame(['status', 'operation', 'diagnostic_id', 'title', 'remediation', 'violation'], array_keys($failure));
    }

    public function testRealRepositoryCheckSucceedsWithThirtyOneSourcesAndNoWrite(): void
    {
        $before = hash_file('sha256', $this->frameworkRoot() . '/README.md');
        [$exit, $stdout, $stderr] = $this->executeCommand(['check', $this->frameworkRoot()]);
        $result = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($result);
        self::assertSame(0, $exit);
        self::assertSame('', $stderr);
        self::assertSame('ok', $result['status']);
        self::assertSame('check', $result['operation']);
        self::assertSame(31, $result['source_count']);
        self::assertSame($before, hash_file('sha256', $this->frameworkRoot() . '/README.md'));
    }

    public function testGenerateThenValidateSucceedsInExternalTemporaryDirectory(): void
    {
        $directory = $this->temporaryDirectory();
        try {
            [$generateExit, $generateOut, $generateErr] = $this->executeCommand(['generate', $this->frameworkRoot(), $directory]);
            $generated = json_decode($generateOut, true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($generated);
            self::assertIsString($generated['artifact_file']);
            self::assertIsString($generated['artifact_sha256']);
            self::assertSame(0, $generateExit);
            self::assertSame('', $generateErr);
            self::assertSame(['status', 'operation', 'artifact_sha256', 'source_set_sha256', 'source_count', 'artifact_file'], array_keys($generated));
            self::assertFileExists($directory . '/' . $generated['artifact_file']);

            [$validateExit, $validateOut, $validateErr] = $this->executeCommand(['validate', $this->frameworkRoot(), $directory . '/' . $generated['artifact_file']]);
            self::assertSame(0, $validateExit);
            self::assertSame('', $validateErr);
            $validated = json_decode($validateOut, true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($validated);
            self::assertSame($generated['artifact_sha256'], $validated['artifact_sha256']);
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testUsageDataSourceAndPublicationFailuresAreStructuredAndBounded(): void
    {
        $directory = $this->temporaryDirectory();
        try {
            $invalidArtifact = $directory . '/invalid.json';
            file_put_contents($invalidArtifact, "{\"secret\":true}\n");
            foreach ([
                [['check', $directory . '/missing'], 64],
                [['validate', $this->frameworkRoot(), $invalidArtifact], 65],
                [['generate', $this->frameworkRoot(), $directory . '/missing'], 64],
            ] as [$arguments, $expectedExit]) {
                [$exit, $stdout, $stderr] = $this->executeCommand($arguments);
                self::assertSame($expectedExit, $exit);
                self::assertSame('', $stdout);
                $failure = json_decode($stderr, true, flags: JSON_THROW_ON_ERROR);
                self::assertIsArray($failure);
                self::assertIsString($failure['diagnostic_id']);
                self::assertSame('error', $failure['status']);
                self::assertMatchesRegularExpression('/^KGN-KNOWLEDGE-1\d{3}$/D', $failure['diagnostic_id']);
            }
        } finally {
            $this->removeTree($directory);
        }
    }

    public function testFailureOutputExcludesStackNativeAbsoluteSourceEnvironmentAndRejectedValues(): void
    {
        $secret = 'rejected-command-' . bin2hex(random_bytes(8));
        [$exit, $stdout, $stderr] = $this->executeCommand([$secret]);
        self::assertSame(64, $exit);
        self::assertSame('', $stdout);
        self::assertStringNotContainsString($secret, $stderr);
        self::assertStringNotContainsString($this->frameworkRoot(), $stderr);
        self::assertStringNotContainsString('Stack trace', $stderr);
        self::assertStringNotContainsString((string) getenv('PATH'), $stderr);
        $failure = json_decode($stderr, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($failure);
        self::assertSame('command', $failure['operation']);
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{int, string, string}
     */
    private function executeCommand(array $arguments): array
    {
        $stdout = fopen('php://temp', 'w+b');
        $stderr = fopen('php://temp', 'w+b');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);
        $exit = (new KnowledgeCommand())->run($arguments, $stdout, $stderr);
        rewind($stdout);
        rewind($stderr);
        $out = stream_get_contents($stdout);
        $err = stream_get_contents($stderr);
        fclose($stdout);
        fclose($stderr);

        return [$exit, (string) $out, (string) $err];
    }

    private function frameworkRoot(): string
    {
        return dirname(__DIR__, 5);
    }

    private function temporaryDirectory(): string
    {
        $path = sys_get_temp_dir() . '/kongono-knowledge-command-' . bin2hex(random_bytes(8));
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
