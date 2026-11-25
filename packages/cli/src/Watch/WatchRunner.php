<?php

declare(strict_types=1);

namespace Kongono\Cli\Watch;

use Closure;
use FilesystemIterator;
use Kongono\Cli\Diagnostics\KongonoAppInspector;
use Kongono\Cli\Policy\PolicyVerifier;
use Kongono\Core\Diagnostics\ErrorExplainer;
use Kongono\Http\App;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

/**
 * Continuous Framework-Native Watch Runner (`php vendor/bin/kongo watch`).
 *
 * Watches `src/`, `app/`, `views/`, and `tests/` in pure PHP 8.5 with zero Node.js,
 * re-running 17-rule `KongonoAppInspector`, `PolicyVerifier`, and PHPUnit tests on every save.
 */
final readonly class WatchRunner
{
    /** @var list<string> */
    private const array IGNORED_DIRS = ['vendor', 'node_modules', '.git', 'var', 'storage', '.cache'];

    /** @var list<string> */
    private const array WATCH_DIRS = ['src', 'app', 'views', 'tests'];

    /**
     * Snapshot `relativePath => mtime:size` across watched directories.
     *
     * @return array<string, string>
     */
    public function snapshot(string $appRoot): array
    {
        $real = realpath($appRoot);
        $root = rtrim(false !== $real ? $real : $appRoot, '\\/');
        $map = [];

        foreach (self::WATCH_DIRS as $sub) {
            $dir = $root . DIRECTORY_SEPARATOR . $sub;
            if (!is_dir($dir)) {
                continue;
            }
            $dirIt = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
            $filter = new RecursiveCallbackFilterIterator(
                $dirIt,
                static fn(SplFileInfo $cur): bool => !$cur->isLink() && (!$cur->isDir() || !in_array($cur->getFilename(), self::IGNORED_DIRS, true)),
            );
            $it = new RecursiveIteratorIterator($filter);
            foreach ($it as $file) {
                if (!$file instanceof SplFileInfo || $file->isLink() || !$file->isFile()) {
                    continue;
                }
                $name = strtolower($file->getFilename());
                if (!str_ends_with($name, '.php') && !str_ends_with($name, '.kgn.html')) {
                    continue;
                }
                $rel = $this->relativePath($file->getPathname(), $root);
                $map[$rel] = $file->getMTime() . ':' . $file->getSize();
            }
        }
        ksort($map);

        return $map;
    }

    /**
     * Compute list of relative paths added, modified, or removed between two snapshots.
     *
     * @param array<string, string> $before
     * @param array<string, string> $after
     * @return list<string>
     */
    public function diffSnapshots(array $before, array $after): array
    {
        $changed = [];
        foreach ($after as $path => $sig) {
            if (($before[$path] ?? null) !== $sig) {
                $changed[] = $path;
            }
        }
        foreach (array_keys($before) as $path) {
            if (!array_key_exists($path, $after)) {
                $changed[] = $path;
            }
        }
        sort($changed);

        return $changed;
    }

    /**
     * Execute one full watch verification cycle (diagnose + policy:check + optional PHPUnit).
     *
     * @param list<string>      $changedFiles
     * @param list<string>|null $phpunitCommand
     * @return array{passed: bool, output: string, diagnose_score: int, policy_score: int, tests_passed: ?bool}
     */
    public function runCycle(
        string $appRoot,
        array $changedFiles = [],
        bool $runTests = true,
        ?array $phpunitCommand = null,
    ): array {
        $t0 = hrtime(true);
        $real = realpath($appRoot);
        $root = rtrim(false !== $real ? $real : $appRoot, '\\/');

        $app = null;
        $bootError = null;
        $bootstrap = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_file($bootstrap)) {
            try {
                $loaded = require $bootstrap;
                if ($loaded instanceof App) {
                    $app = $loaded;
                }
            } catch (Throwable $e) {
                $bootError = (new ErrorExplainer())->explainThrowable($e, basePath: $root)->toArray();
            }
        }

        $diag = (new KongonoAppInspector())->inspect($root, $app);
        $policy = (new PolicyVerifier())->verify($root, $app);

        $testsPassed = null;
        $testOutput = '';
        $staticPassed = null === $bootError && $diag['passed'] && $policy['passed'];
        if ($runTests && $staticPassed && null !== $phpunitCommand && [] !== $phpunitCommand) {
            $cmd = $phpunitCommand;
            // Smart narrowing: if only one *Test.php changed, run that test file first for sub-second feedback
            $changedTests = array_values(array_filter(
                $changedFiles,
                static fn(string $p): bool => str_ends_with($p, 'Test.php') && is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $p)),
            ));
            if (1 === count($changedTests)) {
                $cmd[] = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $changedTests[0]);
            }
            [$exit, $out] = $this->runCommand($cmd, $root);
            $testsPassed = 0 === $exit;
            $testOutput = trim($out);
        }

        $elapsedMs = round((hrtime(true) - $t0) / 1_000_000, 1);
        $allPassed = $staticPassed && (false !== $testsPassed);

        $lines = [
            sprintf(
                '[%s] Kongono Watch — %s (%.1fms)%s',
                gmdate('H:i:s'),
                $allPassed ? 'PASS' : 'FAIL',
                $elapsedMs,
                [] !== $changedFiles ? ' | Changed: ' . implode(', ', array_slice($changedFiles, 0, 4)) : '',
            ),
            sprintf(
                '  [%-4s] diagnose (%d/100)  [%-4s] policy:check (%d/100)%s',
                null === $bootError && $diag['passed'] ? 'PASS' : 'FAIL',
                null === $bootError ? $diag['score'] : 0,
                $policy['passed'] ? 'PASS' : 'FAIL',
                $policy['score'],
                null !== $testsPassed ? sprintf('  [%-4s] phpunit', $testsPassed ? 'PASS' : 'FAIL') : '',
            ),
        ];

        if (null !== $bootError) {
            $lines[] = sprintf('  [BOOT] %s: %s', $bootError['code'], $bootError['what']);
            $lines[] = sprintf('         Why: %s', $bootError['why']);
        }
        foreach (array_slice($diag['findings'], 0, 5) as $f) {
            $lines[] = sprintf('  [%-4s] %s (%s): %s', strtoupper($f['severity']), $f['code'], $f['where'], $f['what']);
            if ([] !== $f['how_to_fix']) {
                $lines[] = '         Fix: ' . $f['how_to_fix'][0];
            }
        }
        foreach (array_slice($policy['violations'], 0, 5) as $v) {
            $lines[] = sprintf('  [POL ] %s (%s): %s', $v['code'], $v['where'], $v['what']);
        }
        if ($diag['fixable_count'] > 0) {
            $lines[] = sprintf('  Tip: %d issue(s) auto-fixable via `php vendor/bin/kongo diagnose --fix`.', $diag['fixable_count']);
        }
        if (false === $testsPassed && '' !== $testOutput) {
            $lines[] = '';
            $lines[] = substr($testOutput, 0, 1200);
        }

        return [
            'passed' => $allPassed,
            'output' => implode("\n", $lines) . "\n",
            'diagnose_score' => null === $bootError ? $diag['score'] : 0,
            'policy_score' => $policy['score'],
            'tests_passed' => $testsPassed,
        ];
    }

    /**
     * Run continuous watch loop (or single shot when `$once = true`).
     *
     * @param list<string>|null           $phpunitCommand
     * @param (Closure(string): void)|null $writer
     * @return array{0: int, 1: string}
     */
    public function watch(
        string $appRoot,
        int $intervalMs = 500,
        bool $once = false,
        bool $runTests = true,
        ?array $phpunitCommand = null,
        ?Closure $writer = null,
        ?int $maxCycles = null,
    ): array {
        $emit = $writer ?? static function (string $text): void {
            fwrite(STDOUT, $text);
            fflush(STDOUT);
        };

        $lastSnap = $this->snapshot($appRoot);
        $initial = $this->runCycle($appRoot, [], $runTests, $phpunitCommand);
        if ($once) {
            return [$initial['passed'] ? 0 : 1, $initial['output']];
        }

        $emit(sprintf("Kongono Watch active on %s (Ctrl+C to stop)...\n\n", $appRoot));
        $emit($initial['output'] . "\n");

        $sleepUs = max(100, min(5000, $intervalMs)) * 1000;
        $cycles = 1;
        while (null === $maxCycles || $cycles < $maxCycles) {
            usleep($sleepUs);
            $nextSnap = $this->snapshot($appRoot);
            $changed = $this->diffSnapshots($lastSnap, $nextSnap);
            if ([] === $changed) {
                continue;
            }
            $lastSnap = $nextSnap;
            ++$cycles;
            $res = $this->runCycle($appRoot, $changed, $runTests, $phpunitCommand);
            $emit($res['output'] . "\n");
        }

        return [0, ''];
    }

    /**
     * @param list<string> $cmd
     * @return array{0: int, 1: string}
     */
    private function runCommand(array $cmd, string $cwd): array
    {
        $proc = @proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
        if (!is_resource($proc)) {
            return [1, 'Unable to launch test runner.'];
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $open = [1 => $pipes[1], 2 => $pipes[2]];
        while ([] !== $open) {
            $read = array_values($open);
            $write = null;
            $except = null;
            $ready = @stream_select($read, $write, $except, 1);
            if (false === $ready) {
                break;
            }
            foreach ($read as $stream) {
                $chunk = fread($stream, 65536);
                if (false === $chunk || ('' === $chunk && feof($stream))) {
                    foreach ($open as $k => $s) {
                        if ($s === $stream) {
                            unset($open[$k]);
                        }
                    }
                    continue;
                }
                if ($stream === $pipes[1]) {
                    $stdout .= $chunk;
                } else {
                    $stderr .= $chunk;
                }
            }
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        return [$code, trim($stdout . "\n" . $stderr)];
    }

    private function relativePath(string $absPath, string $root): string
    {
        $norm = str_replace('\\', '/', $absPath);
        $base = rtrim(str_replace('\\', '/', $root), '/') . '/';

        return str_starts_with($norm, $base) ? substr($norm, strlen($base)) : basename($norm);
    }
}
