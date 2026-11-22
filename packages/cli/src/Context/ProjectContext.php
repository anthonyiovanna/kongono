<?php

declare(strict_types=1);

namespace Kongono\Cli\Context;

use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Core\Graph\ApplicationGraph;
use Kongono\Http\App;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Safe, secret-scrubbed view of a Kongono application directory and its live ApplicationGraph.
 * Feeds accurate project context to Oshie, Chie, and Hantei.
 */
final readonly class ProjectContext
{
    public string $rootPath;

    public function __construct(
        string $rootPath,
        public ?ApplicationGraph $graph = null,
    ) {
        $resolved = realpath($rootPath);
        if (false === $resolved || !is_dir($resolved)) {
            throw new CoreException(sprintf('Project root directory does not exist: "%s".', $rootPath));
        }
        $this->rootPath = $resolved;
    }

    /** Load a Kongono app from app/bootstrap.php (if present) and capture its live ApplicationGraph. */
    public static function discover(string $rootPath): self
    {
        $resolved = realpath($rootPath);
        if (false === $resolved || !is_dir($resolved)) {
            throw new CoreException(sprintf('Project root directory does not exist: "%s".', $rootPath));
        }

        $graph = null;
        $bootstrap = $resolved . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_file($bootstrap)) {
            try {
                $app = require $bootstrap;
                if ($app instanceof App) {
                    $graph = $app->graph();
                }
            } catch (\Throwable) {
                // Gracefully degrade when inspecting a newly scaffolded or broken app
                $graph = null;
            }
        }

        return new self($resolved, $graph);
    }

    /**
     * List relative paths of project source/template/config files (excluding vendor/var/.git).
     *
     * @return list<string>
     */
    public function listFiles(int $limit = 100): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->rootPath));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($this->rootPath) + 1));
            if (
                str_starts_with($relative, 'vendor/')
                || str_starts_with($relative, '.git/')
                || str_starts_with($relative, 'var/')
                || str_starts_with($relative, '.cache/')
                || str_starts_with($relative, '.phpunit.cache/')
            ) {
                continue;
            }
            $files[] = $relative;
            if (count($files) >= $limit) {
                break;
            }
        }

        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * Read one contained file safely, scrubbing common secret patterns before sharing with an AI.
     *
     * @param int<1, max> $maxBytes
     */
    public function readFileSafe(string $relativePath, int $maxBytes = 32768): string
    {
        $fullPath = $this->resolveContainedPath($relativePath, mustExist: true);
        $raw = file_get_contents($fullPath, length: $maxBytes);
        if (false === $raw) {
            throw new CoreException(sprintf('Unable to read "%s".', $relativePath));
        }

        return SecretScrubber::scrub($raw);
    }

    /** Write a file strictly within the project root (rejects path traversal). */
    public function writeFileContained(string $relativePath, string $content): string
    {
        $fullPath = $this->resolveContainedPath($relativePath, mustExist: false);
        $dir = dirname($fullPath);
        if (!is_dir($dir) && !@mkdir($dir, 0o775, true) && !is_dir($dir)) {
            throw new CoreException(sprintf('Unable to create directory for "%s".', $relativePath));
        }
        if (false === file_put_contents($fullPath, $content, LOCK_EX)) {
            throw new CoreException(sprintf('Unable to write file "%s".', $relativePath));
        }

        return $fullPath;
    }

    /** Build a concise, secret-scrubbed summary prompt block describing the current Kongono project. */
    public function promptSummary(): string
    {
        $lines = [
            'Project root: ' . basename($this->rootPath),
            'Files: ' . implode(', ', $this->listFiles(60)),
        ];

        if (null !== $this->graph) {
            $lines[] = 'Live ApplicationGraph JSON:';
            $lines[] = $this->graph->toJson(pretty: false);
        }

        return SecretScrubber::scrub(implode("\n", $lines));
    }

    private function resolveContainedPath(string $relativePath, bool $mustExist): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if ('' === $normalized || str_contains($normalized, '..') || str_contains($normalized, "\0")) {
            throw new CoreException(sprintf('Path "%s" is invalid or attempts directory traversal.', $relativePath));
        }

        $candidate = $this->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        $prefix = rtrim($this->rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if ($mustExist) {
            if (is_link($candidate)) {
                throw new CoreException(sprintf('Symlink "%s" is not permitted.', $relativePath));
            }
            $real = realpath($candidate);
            if (false === $real || !is_file($real) || !str_starts_with($real, $prefix)) {
                throw new CoreException(sprintf('File "%s" does not exist within project root.', $relativePath));
            }

            return $real;
        }

        // Verify nearest existing ancestor directory is strictly contained inside rootPath (blocks symlink escape)
        $probeDir = dirname($candidate);
        while (!file_exists($probeDir) && $probeDir !== dirname($probeDir)) {
            $probeDir = dirname($probeDir);
        }
        $realDir = realpath($probeDir);
        if (false === $realDir || ($realDir !== $this->rootPath && !str_starts_with($realDir . DIRECTORY_SEPARATOR, $prefix))) {
            throw new CoreException(sprintf('Target directory for "%s" escapes project root.', $relativePath));
        }

        return $candidate;
    }
}
