<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Application;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplFileInfo;

final class Phase5ApplicationAcceptanceTest extends TestCase
{
    public function testRealHarnessIsDeterministicAndPreservesTheRepository(): void
    {
        $root = self::root();
        $before = $this->protectedIdentity($root);
        $temporaryBefore = $this->acceptanceTemporaryPaths();

        $first = $this->runProcess([PHP_BINARY, $root . '/tests/External/Application/run.php'], $root);
        self::assertTrue($first->started());
        self::assertNull($first->blockedReason());
        self::assertSame(0, $first->exitCode());
        self::assertSame('', $first->stderr());
        $decoded = $this->canonicalObject($first->stdout());
        $this->assertAcceptanceResult($decoded);
        self::assertSame($before, $this->protectedIdentity($root));
        self::assertSame($temporaryBefore, $this->acceptanceTemporaryPaths());

        $second = $this->runProcess([PHP_BINARY, $root . '/tests/External/Application/run.php'], $root);
        self::assertTrue($second->started());
        self::assertNull($second->blockedReason());
        self::assertSame(0, $second->exitCode());
        self::assertSame('', $second->stderr());
        self::assertSame($first->stdout(), $second->stdout());
        self::assertSame($decoded, $this->canonicalObject($second->stdout()));
        self::assertSame($before, $this->protectedIdentity($root));
        self::assertSame($temporaryBefore, $this->acceptanceTemporaryPaths());
    }

    /** @param array<string, mixed> $result */
    private function assertAcceptanceResult(array $result): void
    {
        self::assertSame([
            'schema',
            'version',
            'status',
            'scenario_count',
            'passed',
            'failed',
            'scenarios',
            'composition_artifact_sha256',
            'generated_wiring_sha256',
            'skeleton_sha256',
        ], array_keys($result));
        self::assertSame('kongono.phase5-external-acceptance', $result['schema']);
        self::assertSame(1, $result['version']);
        self::assertSame('PASS', $result['status']);
        self::assertSame(28, $result['scenario_count']);
        self::assertSame(28, $result['passed']);
        self::assertSame(0, $result['failed']);
        self::assertIsArray($result['scenarios']);
        self::assertTrue(array_is_list($result['scenarios']));
        $scenarioIds = [];
        foreach ($result['scenarios'] as $scenario) {
            self::assertIsArray($scenario);
            self::assertSame(['id', 'status'], array_keys($scenario));
            self::assertIsString($scenario['id']);
            self::assertSame('PASS', $scenario['status']);
            $scenarioIds[] = $scenario['id'];
        }
        self::assertSame(AcceptanceResult::SCENARIOS, $scenarioIds);
        foreach (['composition_artifact_sha256', 'generated_wiring_sha256', 'skeleton_sha256'] as $key) {
            self::assertIsString($result[$key]);
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $result[$key]);
        }
    }

    /** @return array<string, mixed> */
    private function canonicalObject(string $bytes): array
    {
        self::assertNotSame('', $bytes);
        self::assertStringEndsWith("\n", $bytes);
        self::assertFalse(str_ends_with($bytes, "\n\n"));
        self::assertStringNotContainsString("\r", $bytes);
        self::assertFalse(str_starts_with($bytes, "\xEF\xBB\xBF"));
        $decoded = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertFalse(array_is_list($decoded));
        self::assertSame(json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR, 32) . "\n", $bytes);

        $object = [];
        foreach ($decoded as $key => $value) {
            self::assertIsString($key);
            $object[$key] = $value;
        }

        return $object;
    }

    /** @return array{status: string, index: string, lock: string, head_tree: string} */
    private function protectedIdentity(string $root): array
    {
        $status = $this->runProcess(['git', '-c', 'safe.directory=' . $root, 'status', '--porcelain=v1', '--untracked-files=all'], $root);
        self::assertSame(0, $status->exitCode());
        $index = $this->runProcess(['git', '-c', 'safe.directory=' . $root, 'diff-index', '--cached', '--quiet', 'HEAD'], $root);
        self::assertSame(0, $index->exitCode());
        $tree = $this->runProcess(['git', '-c', 'safe.directory=' . $root, 'rev-parse', 'HEAD^{tree}'], $root);
        self::assertSame(0, $tree->exitCode());
        $lock = file_get_contents($root . '/composer.lock');
        self::assertIsString($lock);

        return [
            'status' => $status->stdout(),
            'index' => (string) $index->exitCode(),
            'lock' => hash('sha256', $lock),
            'head_tree' => trim($tree->stdout()),
        ];
    }

    /** @return list<string> */
    private function acceptanceTemporaryPaths(): array
    {
        $temporary = realpath(sys_get_temp_dir());
        self::assertNotFalse($temporary);
        $paths = [];
        $iterator = new FilesystemIterator($temporary, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO);
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo) {
                throw new RuntimeException('temporary_entry_invalid');
            }
            if (str_starts_with($entry->getFilename(), 'kongono-phase5h-')) {
                $paths[] = $entry->getFilename();
            }
        }
        sort($paths, SORT_STRING);

        return $paths;
    }

    /** @param list<string> $command */
    private function runProcess(array $command, string $workingDirectory): ProcessResult
    {
        $pipes = [];
        $process = proc_open(
            $command,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            $workingDirectory,
            null,
            ['bypass_shell' => true, 'suppress_errors' => true, 'binary_pipes' => true],
        );
        if (!is_resource($process) || 3 !== count($pipes)) {
            return new ProcessResult(false, null, '', '', 'process-start-failed');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $deadline = microtime(true) + 1_200;
        $observedExit = null;
        $blocked = null;
        try {
            while (true) {
                $this->drain($pipes[1], $stdout);
                $this->drain($pipes[2], $stderr);
                if (strlen($stdout) > 8_388_608 || strlen($stderr) > 8_388_608) {
                    $blocked = 'output-limit';
                    proc_terminate($process);
                    break;
                }
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $observedExit = $status['exitcode'] >= 0 ? $status['exitcode'] : null;
                    break;
                }
                if (microtime(true) >= $deadline) {
                    $blocked = 'process-timeout';
                    proc_terminate($process);
                    break;
                }
                usleep(10_000);
            }
            stream_set_blocking($pipes[1], true);
            stream_set_blocking($pipes[2], true);
            $this->drain($pipes[1], $stdout);
            $this->drain($pipes[2], $stderr);
        } finally {
            foreach ([1, 2] as $index) {
                if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                    fclose($pipes[$index]);
                }
            }
        }
        $closedExit = proc_close($process);
        $exit = null !== $observedExit ? $observedExit : ($closedExit >= 0 ? $closedExit : null);

        return new ProcessResult(true, $exit, $stdout, $stderr, $blocked);
    }

    /** @param resource $stream */
    private function drain(mixed $stream, string &$buffer): void
    {
        while (true) {
            $bytes = fread($stream, 65_536);
            if (false === $bytes || '' === $bytes) {
                return;
            }
            $buffer .= $bytes;
        }
    }

    private static function root(): string
    {
        $root = realpath(dirname(__DIR__, 3));
        if (false === $root) {
            throw new RuntimeException('repository_root_unavailable');
        }

        return str_replace('\\', '/', $root);
    }
}
