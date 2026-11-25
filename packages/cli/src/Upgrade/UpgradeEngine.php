<?php

declare(strict_types=1);

namespace Kongono\Cli\Upgrade;

use FilesystemIterator;
use Kongono\Cli\Companions\DiffRenderer;
use Kongono\Cli\Diagnostics\AppAutoFixer;
use Kongono\Cli\Diagnostics\KongonoAppInspector;
use Kongono\Cli\Policy\PolicyVerifier;
use Kongono\Http\App;
use Kongono\View\TemplateCompiler;
use ParseError;
use PhpToken;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Verifiable Self-Upgrading Codemod Engine (`kongo upgrade [--plan] [--apply] [--verify]`).
 *
 * Runs deterministic PHP 8.5 & `.kgn.html` codemods, validates every transformed file with the
 * Zend PHP 8.5 parser BEFORE writing to disk, renders unified diffs, and optionally verifies
 * the upgraded app via `KongonoAppInspector` + `PolicyVerifier`.
 */
final readonly class UpgradeEngine
{
    /** @var list<string> */
    private const array IGNORED_DIRS = ['vendor', 'node_modules', '.git', 'var', 'storage', '.cache'];

    /** @var list<CodemodInterface> */
    private array $codemods;

    /** @param list<CodemodInterface>|null $codemods */
    public function __construct(?array $codemods = null)
    {
        $this->codemods = $codemods ?? self::builtInCodemods();
    }

    /** @return list<CodemodInterface> */
    public static function builtInCodemods(): array
    {
        return [
            // 1. Enforce PHP 8.5 `declare(strict_types=1);` in src/ and app/
            new class implements CodemodInterface {
                public function id(): string
                {
                    return 'php85-strict-types';
                }

                public function title(): string
                {
                    return 'Ensure `declare(strict_types=1);` in PHP 8.5 source files';
                }

                public function targetVersion(): string
                {
                    return '1.19';
                }

                public function supportsFile(string $relativePath): bool
                {
                    return str_ends_with($relativePath, '.php')
                        && (str_starts_with($relativePath, 'src/') || str_starts_with($relativePath, 'app/'));
                }

                public function transform(string $relativePath, string $content): array
                {
                    if (!str_starts_with(ltrim($content), '<?php') || 1 === preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)/i', $content)) {
                        return ['changed' => false, 'updated' => $content, 'summary' => ''];
                    }

                    $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";
                    $updated = preg_replace('/\A(\s*<\?php\b[^\r\n]*\r?\n)/', '$1' . $eol . 'declare(strict_types=1);' . $eol, $content, 1);
                    if (!is_string($updated) || $updated === $content) {
                        return ['changed' => false, 'updated' => $content, 'summary' => ''];
                    }

                    return ['changed' => true, 'updated' => $updated, 'summary' => 'Inserted `declare(strict_types=1);`'];
                }
            },

            // 2. Template CSRF `_token` -> `_csrf` + Live 2.5 `x = y` -> `set(x, y)`
            new class implements CodemodInterface {
                public function id(): string
                {
                    return 'kongono-template-v1';
                }

                public function title(): string
                {
                    return 'Upgrade `_token` -> `_csrf` and Live 2.5 `x = y` -> `set(x, y)`';
                }

                public function targetVersion(): string
                {
                    return '1.19';
                }

                public function supportsFile(string $relativePath): bool
                {
                    return str_ends_with($relativePath, '.kgn.html') && str_starts_with($relativePath, 'views/');
                }

                public function transform(string $relativePath, string $content): array
                {
                    $res = (new AppAutoFixer())->fixContent($relativePath, $content);
                    if (!$res['changed']) {
                        return ['changed' => false, 'updated' => $content, 'summary' => ''];
                    }

                    return [
                        'changed' => true,
                        'updated' => $res['updated'],
                        'summary' => sprintf('Applied %d template fix(es)', count($res['applied'])),
                    ];
                }
            },

            // 3. Template `@for ($i = 0; $i < $n; $i++)` -> `@foreach (range(0, ($n) - 1) as $i)`
            new class implements CodemodInterface {
                public function id(): string
                {
                    return 'template-for-to-foreach';
                }

                public function title(): string
                {
                    return 'Convert unsupported `@for (...)` loops to `@foreach (range(...))`';
                }

                public function targetVersion(): string
                {
                    return '1.19';
                }

                public function supportsFile(string $relativePath): bool
                {
                    return str_ends_with($relativePath, '.kgn.html') && str_starts_with($relativePath, 'views/');
                }

                public function transform(string $relativePath, string $content): array
                {
                    if (!str_contains($content, '@for')) {
                        return ['changed' => false, 'updated' => $content, 'summary' => ''];
                    }

                    $rewrote = false;
                    /** @var list<bool> $forStack */
                    $forStack = [];
                    $updated = preg_replace_callback(
                        '/@for\b\s*\(([^)]*)\)|@endfor\b/',
                        static function (array $m) use (&$rewrote, &$forStack): string {
                            $full = $m[0];
                            if (str_starts_with($full, '@endfor')) {
                                $wasRewritten = array_pop($forStack);

                                return true === $wasRewritten ? '@endforeach' : $full;
                            }

                            $expr = $m[1] ?? '';
                            if (1 !== preg_match('/\A\s*(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(\d+)\s*;\s*\1\s*<\s*([^;]+)\s*;\s*(?:\1\+\+|\+\+\1)\s*\z/', $expr, $parts)) {
                                $forStack[] = false;

                                return $full;
                            }

                            $rewrote = true;
                            $forStack[] = true;
                            $var = $parts[1];
                            $start = (int) $parts[2];
                            $bound = trim($parts[3]);
                            if (ctype_digit($bound)) {
                                $end = ((int) $bound) - 1;
                                if ($end < $start) {
                                    return sprintf('@foreach ([] as %s)', $var);
                                }

                                return sprintf('@foreach (range(%d, %d) as %s)', $start, $end, $var);
                            }

                            return sprintf('@foreach (((%s) > %d ? range(%d, (%s) - 1) : []) as %s)', $bound, $start, $start, $bound, $var);
                        },
                        $content,
                    );
                    if (!$rewrote || !is_string($updated)) {
                        return ['changed' => false, 'updated' => $content, 'summary' => ''];
                    }

                    return ['changed' => true, 'updated' => $updated, 'summary' => 'Converted `@for` loop to `@foreach (range(...))`'];
                }
            },
        ];
    }

    /**
     * Plan or apply codemods across an application root, syntax-verifying every transform before writing.
     *
     * @return array{
     *     mode: 'plan'|'apply',
     *     codemods_available: list<array{id: string, title: string, target_version: string}>,
     *     files_scanned: int,
     *     files_changed: int,
     *     transforms_applied: int,
     *     skipped_unsafe: int,
     *     changes: list<array{file: string, codemods: list<string>, summaries: list<string>, diff: string}>,
     *     verification: ?array{diagnose_passed: bool, diagnose_score: int, policy_passed: bool, policy_score: int}
     * }
     */
    public function run(string $appRoot, bool $apply = false, ?App $app = null, bool $verify = false): array
    {
        $real = realpath($appRoot);
        $root = rtrim(false !== $real ? $real : $appRoot, '\\/');
        $rootPrefix = $root . DIRECTORY_SEPARATOR;

        $filesScanned = 0;
        $transformsApplied = 0;
        $skippedUnsafe = 0;
        $changes = [];

        foreach (['src', 'app', 'views'] as $sub) {
            $dir = $root . DIRECTORY_SEPARATOR . $sub;
            if (!is_dir($dir) || is_link($dir)) {
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
                $realFile = realpath($file->getPathname());
                if (false === $realFile || !str_starts_with($realFile, $rootPrefix)) {
                    continue;
                }
                $rel = $this->relativePath($realFile, $root);
                if (!str_ends_with($rel, '.php') && !str_ends_with($rel, '.kgn.html')) {
                    continue;
                }
                $original = file_get_contents($realFile);
                if (false === $original) {
                    continue;
                }

                ++$filesScanned;
                $current = $original;
                $appliedIds = [];
                $summaries = [];

                foreach ($this->codemods as $mod) {
                    if (!$mod->supportsFile($rel)) {
                        continue;
                    }
                    $res = $mod->transform($rel, $current);
                    if (!$res['changed'] || $res['updated'] === $current) {
                        continue;
                    }
                    // Safety invariant: NEVER accept a transform that fails Zend PHP 8.5 parser verification!
                    if (!$this->verifyTransformedSyntax($rel, $res['updated'])) {
                        ++$skippedUnsafe;
                        continue;
                    }
                    $current = $res['updated'];
                    $appliedIds[] = $mod->id();
                    $summaries[] = $res['summary'];
                    ++$transformsApplied;
                }

                if ($current !== $original) {
                    if ($apply) {
                        file_put_contents($realFile, $current, LOCK_EX);
                    }
                    $changes[] = [
                        'file' => $rel,
                        'codemods' => $appliedIds,
                        'summaries' => $summaries,
                        'diff' => DiffRenderer::render($rel, $original, $current),
                    ];
                }
            }
        }

        $verification = null;
        if ($verify) {
            $diag = (new KongonoAppInspector())->inspect($root, $app);
            $policy = (new PolicyVerifier())->verify($root, $app);
            $verification = [
                'diagnose_passed' => $diag['passed'],
                'diagnose_score' => $diag['score'],
                'policy_passed' => $policy['passed'],
                'policy_score' => $policy['score'],
            ];
        }

        return [
            'mode' => $apply ? 'apply' : 'plan',
            'codemods_available' => array_map(static fn(CodemodInterface $m): array => [
                'id' => $m->id(),
                'title' => $m->title(),
                'target_version' => $m->targetVersion(),
            ], $this->codemods),
            'files_scanned' => $filesScanned,
            'files_changed' => count($changes),
            'transforms_applied' => $transformsApplied,
            'skipped_unsafe' => $skippedUnsafe,
            'changes' => $changes,
            'verification' => $verification,
        ];
    }

    private function verifyTransformedSyntax(string $rel, string $content): bool
    {
        try {
            if (str_ends_with($rel, '.php')) {
                PhpToken::tokenize($content, TOKEN_PARSE);

                return true;
            }
            if (str_ends_with($rel, '.kgn.html')) {
                $compiled = (new TemplateCompiler())->compile($content);
                PhpToken::tokenize($compiled, TOKEN_PARSE);

                return true;
            }
        } catch (ParseError) {
            return false;
        }

        return true;
    }

    private function relativePath(string $absPath, string $root): string
    {
        $norm = str_replace('\\', '/', $absPath);
        $base = rtrim(str_replace('\\', '/', $root), '/') . '/';

        return str_starts_with($norm, $base) ? substr($norm, strlen($base)) : basename($norm);
    }
}
