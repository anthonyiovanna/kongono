<?php

declare(strict_types=1);

namespace Kongono\Cli\Skills;

use Kongono\Core\CoreException;

/**
 * Installs bundled Kongono workflow skills (`skills/<name>/SKILL.md`) into a project's
 * `.agents/skills/` (Antigravity, Codex, Cursor, Gemini CLI) and/or `.claude/skills/` (Claude Code).
 */
final readonly class SkillsInstaller
{
    /** @var array<string, string> */
    private const array TARGET_DIRS = [
        'agents' => '.agents/skills',
        'claude' => '.claude/skills',
    ];

    private string $sourceDir;

    public function __construct(?string $sourceDir = null)
    {
        $this->sourceDir = $sourceDir ?? (dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'skills');
    }

    /** @return list<string> Sorted bundled skill names */
    public function available(): array
    {
        $realSource = realpath($this->sourceDir);
        if (false === $realSource || !is_dir($realSource)) {
            return [];
        }

        $entries = scandir($realSource);
        if (false === $entries) {
            return [];
        }

        $skills = [];
        foreach ($entries as $entry) {
            if (1 !== preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/', $entry)) {
                continue;
            }
            $dir = $realSource . DIRECTORY_SEPARATOR . $entry;
            $skillFile = $dir . DIRECTORY_SEPARATOR . 'SKILL.md';
            if (is_link($dir) || !is_dir($dir) || is_link($skillFile) || !is_file($skillFile)) {
                continue;
            }
            $skills[] = $entry;
        }
        sort($skills);

        return $skills;
    }

    /**
     * Install bundled skills into the target application root.
     *
     * @return array{installed: list<string>, updated: list<string>, unchanged: list<string>, skipped: list<string>}
     */
    public function install(string $appRoot, string $target = 'agents', bool $force = false): array
    {
        $normalizedTarget = strtolower(trim($target));
        $destRoots = match ($normalizedTarget) {
            'agents' => [self::TARGET_DIRS['agents']],
            'claude' => [self::TARGET_DIRS['claude']],
            'both' => [self::TARGET_DIRS['agents'], self::TARGET_DIRS['claude']],
            default => throw new CoreException(sprintf('Unsupported skills target "%s". Use agents, claude, or both.', $target)),
        };

        $realAppRoot = realpath($appRoot);
        if (false === $realAppRoot || !is_dir($realAppRoot)) {
            throw new CoreException(sprintf('Application root "%s" does not exist.', $appRoot));
        }

        $realSource = realpath($this->sourceDir);
        if (false === $realSource || !is_dir($realSource)) {
            throw new CoreException(sprintf('Bundled Kongono skills directory "%s" was not found.', $this->sourceDir));
        }

        $skills = $this->available();
        if ([] === $skills) {
            throw new CoreException(sprintf('No bundled SKILL.md files found in "%s".', $realSource));
        }

        $installed = [];
        $updated = [];
        $unchanged = [];
        $skipped = [];

        foreach ($destRoots as $relativeRoot) {
            foreach ($skills as $skill) {
                $srcFile = $realSource . DIRECTORY_SEPARATOR . $skill . DIRECTORY_SEPARATOR . 'SKILL.md';
                $contents = file_get_contents($srcFile);
                if (false === $contents) {
                    throw new CoreException(sprintf('Unable to read bundled skill "%s".', $skill));
                }

                $relativeDir = $relativeRoot . '/' . $skill;
                $destDir = $this->ensureContainedDirectory($realAppRoot, $relativeDir);
                $relativeFile = $relativeDir . '/SKILL.md';
                $destFile = $destDir . DIRECTORY_SEPARATOR . 'SKILL.md';

                if (is_link($destFile)) {
                    throw new CoreException(sprintf('Refusing to overwrite symlink at "%s".', $relativeFile));
                }

                if (is_file($destFile)) {
                    $existing = file_get_contents($destFile);
                    if ($existing === $contents) {
                        $unchanged[] = $relativeFile;
                        continue;
                    }
                    if (!$force) {
                        $skipped[] = $relativeFile;
                        continue;
                    }
                    if (false === file_put_contents($destFile, $contents, LOCK_EX)) {
                        throw new CoreException(sprintf('Unable to update "%s".', $relativeFile));
                    }
                    $updated[] = $relativeFile;
                    continue;
                }

                if (false === file_put_contents($destFile, $contents, LOCK_EX)) {
                    throw new CoreException(sprintf('Unable to write "%s".', $relativeFile));
                }
                $installed[] = $relativeFile;
            }
        }

        return [
            'installed' => $installed,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'skipped' => $skipped,
        ];
    }

    private function ensureContainedDirectory(string $realAppRoot, string $relativeDir): string
    {
        $current = $realAppRoot;
        $prefix = rtrim($realAppRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        foreach (explode('/', $relativeDir) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw new CoreException(sprintf('Invalid target path segment in "%s".', $relativeDir));
            }
            $next = $current . DIRECTORY_SEPARATOR . $segment;
            if (is_link($next)) {
                throw new CoreException(sprintf('Refusing to traverse symlink directory "%s".', $next));
            }
            if (!is_dir($next) && !@mkdir($next, 0o775) && !is_dir($next)) {
                throw new CoreException(sprintf('Unable to create directory "%s".', $next));
            }
            $realNext = realpath($next);
            if (false === $realNext || !str_starts_with($realNext . DIRECTORY_SEPARATOR, $prefix)) {
                throw new CoreException(sprintf('Target directory "%s" escaped application root.', $relativeDir));
            }
            $current = $realNext;
        }

        return $current;
    }
}
