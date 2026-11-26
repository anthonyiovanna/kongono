<?php

declare(strict_types=1);

namespace Kongono\Cli\Diagnostics;

use FilesystemIterator;
use Kongono\Cli\Companions\DiffRenderer;
use Kongono\Core\Env\EnvExampleGenerator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Deterministic, lossless autofixer for unambiguous Kongono template issues (`kongo diagnose --fix` & `kongo lsp`).
 *
 * Intentionally limited to 100% semantics-preserving fixes:
 *  1. `KGN-DIAG-CSRF-FIELD`: `<input ... name="_token">` -> `name="_csrf"`
 *  2. `KGN-DIAG-LIVE-ASSIGN`: `k-on:event="foo = bar"` -> `k-on:event="set(foo, bar)"`
 */
final readonly class AppAutoFixer
{
    /**
     * Apply safe deterministic fixes across `views/*.kgn.html` (or preview diffs when `$dryRun = true`).
     *
     * @return array{
     *     dry_run: bool,
     *     files_changed: int,
     *     total_fixes: int,
     *     changes: list<array{
     *         file: string,
     *         fixes: list<array{code: string, line: int, description: string, original: string, replacement: string}>,
     *         diff: string
     *     }>
     * }
     */
    public function fixDirectory(string $appRoot, bool $dryRun = false): array
    {
        $real = realpath($appRoot);
        $root = rtrim(false !== $real ? $real : $appRoot, '\\/');
        $rootPrefix = $root . DIRECTORY_SEPARATOR;
        $viewsDir = $root . DIRECTORY_SEPARATOR . 'views';

        /** @var list<array{file: string, fixes: list<array{code: string, line: int, description: string, original: string, replacement: string}>, diff: string}> $changes */
        $changes = [];
        $totalFixes = 0;

        if (is_dir($viewsDir) && !is_link($viewsDir)) {
            $dirIt = new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS);
            $filter = new RecursiveCallbackFilterIterator(
                $dirIt,
                static fn(SplFileInfo $cur): bool => !$cur->isLink(),
            );
            $it = new RecursiveIteratorIterator($filter);
            foreach ($it as $file) {
                if (!$file instanceof SplFileInfo || $file->isLink() || !$file->isFile() || !str_ends_with($file->getFilename(), '.kgn.html')) {
                    continue;
                }
                $realFile = realpath($file->getPathname());
                if (false === $realFile || !str_starts_with($realFile, $rootPrefix)) {
                    continue;
                }
                $raw = file_get_contents($realFile);
                if (false === $raw) {
                    continue;
                }
                $rel = $this->relativePath($realFile, $root);
                $result = $this->fixContent($rel, $raw);
                if (!$result['changed']) {
                    continue;
                }

                if (!$dryRun) {
                    file_put_contents($realFile, $result['updated'], LOCK_EX);
                }

                $totalFixes += count($result['applied']);
                $changes[] = [
                    'file' => $rel,
                    'fixes' => $result['applied'],
                    'diff' => DiffRenderer::render($rel, $raw, $result['updated']),
                ];
            }
        }

        $examplePath = $root . DIRECTORY_SEPARATOR . '.env.example';
        if (is_file($examplePath) && !is_link($examplePath)) {
            $envReport = (new EnvExampleGenerator())->sync($root, write: false);
            if ([] !== $envReport['missing_keys'] && $envReport['changed']) {
                $beforeRaw = file_get_contents($examplePath);
                $before = is_string($beforeRaw) ? $beforeRaw : '';
                if (!$dryRun) {
                    file_put_contents($examplePath, $envReport['content'], LOCK_EX);
                }
                $fix = [
                    'code' => 'KGN-DIAG-ENV-DRIFT',
                    'line' => 1,
                    'description' => sprintf(
                        'Append %d missing placeholder key(s) to .env.example: %s.',
                        count($envReport['missing_keys']),
                        implode(', ', $envReport['missing_keys']),
                    ),
                    'original' => '',
                    'replacement' => $envReport['diff_preview'],
                ];
                ++$totalFixes;
                $changes[] = [
                    'file' => '.env.example',
                    'fixes' => [$fix],
                    'diff' => DiffRenderer::render('.env.example', $before, $envReport['content']),
                ];
            }
        }

        return [
            'dry_run' => $dryRun,
            'files_changed' => count($changes),
            'total_fixes' => $totalFixes,
            'changes' => $changes,
        ];
    }

    /**
     * Compute exact line-level fixes for a single `.kgn.html` buffer (reused by both CLI `--fix` and LSP `codeAction`).
     *
     * @return array{
     *     changed: bool,
     *     updated: string,
     *     applied: list<array{code: string, line: int, description: string, original: string, replacement: string}>
     * }
     */
    public function fixContent(string $relativeFile, string $content): array
    {
        if (!str_ends_with(strtolower($relativeFile), '.kgn.html')) {
            return ['changed' => false, 'updated' => $content, 'applied' => []];
        }

        $lineEnding = str_contains($content, "\r\n") ? "\r\n" : "\n";
        $normalized = str_replace("\r\n", "\n", $content);
        $lines = explode("\n", $normalized);
        $inComment = false;

        /** @var list<array{code: string, line: int, description: string, original: string, replacement: string}> $applied */
        $applied = [];

        foreach ($lines as $idx => $line) {
            $lineNo = $idx + 1;
            $updated = $line;

            // Track multi-line `{{-- ... --}}` comments so we never rewrite commented-out examples
            if ($inComment) {
                if (str_contains($line, '--}}')) {
                    $inComment = false;
                }
                continue;
            }
            if (str_contains($line, '{{--') && !str_contains($line, '--}}')) {
                $inComment = true;
                continue;
            }
            if (str_contains($line, '{{--') && str_contains($line, '--}}')) {
                continue;
            }

            // Fix 1: `KGN-DIAG-CSRF-FIELD` (`<input ... name="_token">` -> `name="_csrf"`)
            $afterCsrf = preg_replace_callback(
                '/(<input\b[^>]*\bname\s*=\s*)(["\'])_token\2/i',
                static fn(array $m): string => $m[1] . $m[2] . '_csrf' . $m[2],
                $updated,
            );
            if (is_string($afterCsrf) && $afterCsrf !== $updated) {
                $applied[] = [
                    'code' => 'KGN-DIAG-CSRF-FIELD',
                    'line' => $lineNo,
                    'description' => 'Rename CSRF input from `name="_token"` to Kongono `name="_csrf"`.',
                    'original' => $updated,
                    'replacement' => $afterCsrf,
                ];
                $updated = $afterCsrf;
            }

            // Fix 2: `KGN-DIAG-LIVE-ASSIGN` (`k-on:event="var = val"` -> `k-on:event="set(var, val)"`)
            $afterLive = preg_replace_callback(
                '/(\bk-on:[a-zA-Z0-9._-]+\s*=\s*")([^"]+)(")/',
                static function (array $m): string {
                    $expr = $m[2];
                    $parts = explode(';', $expr);
                    $rewrote = false;
                    foreach ($parts as $pIdx => $part) {
                        $trimmed = trim($part);
                        if (1 === preg_match('/\A([a-zA-Z_][a-zA-Z0-9_]*)\s*=(?!=)\s*(.+)\z/', $trimmed, $am)) {
                            $parts[$pIdx] = sprintf('set(%s, %s)', $am[1], trim($am[2]));
                            $rewrote = true;
                        }
                    }

                    return $rewrote ? ($m[1] . implode('; ', $parts) . $m[3]) : $m[0];
                },
                $updated,
            );
            if (is_string($afterLive) && $afterLive !== $updated) {
                $applied[] = [
                    'code' => 'KGN-DIAG-LIVE-ASSIGN',
                    'line' => $lineNo,
                    'description' => 'Convert Live 2.5 assignment `x = y` to strict-CSP action `set(x, y)`.',
                    'original' => $updated,
                    'replacement' => $afterLive,
                ];
                $updated = $afterLive;
            }

            $lines[$idx] = $updated;
        }

        if ([] === $applied) {
            return ['changed' => false, 'updated' => $content, 'applied' => []];
        }

        return [
            'changed' => true,
            'updated' => implode($lineEnding, $lines),
            'applied' => $applied,
        ];
    }

    private function relativePath(string $absPath, string $root): string
    {
        $norm = str_replace('\\', '/', $absPath);
        $base = rtrim(str_replace('\\', '/', $root), '/') . '/';

        return str_starts_with($norm, $base) ? substr($norm, strlen($base)) : basename($norm);
    }
}
