<?php

declare(strict_types=1);

namespace Kongono\Hantei\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Hantei\Diagnostic\HanteiDiagnostic;
use Kongono\Hantei\Exception\InvalidHanteiRequestException;
use Kongono\Hantei\HanteiCheckEvidence;

/** Derives one bounded exact identity from Git-visible ordinary source files. */
#[Api(ApiStatus::Internal)]
final readonly class FrameworkSubject
{
    public const MAX_FILES = 4096;
    public const MAX_FILE_BYTES = 4_194_304;
    public const MAX_TOTAL_BYTES = 67_108_864;

    private function __construct(private string $root, private string $sha256, private int $fileCount, private int $sourceBytes) {}

    public static function capture(string $frameworkRoot, ?ProcessRunner $runner = null): self
    {
        $resolved = realpath($frameworkRoot);
        if (false === $resolved || !is_dir($resolved) || is_link($resolved)) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::SubjectUnavailable, 'subject-unavailable');
        }
        $runner ??= new ProcessRunner();
        $result = $runner->run([
            'git',
            '-c',
            'safe.directory=' . $resolved,
            '-C',
            $resolved,
            'ls-files',
            '-z',
            '--cached',
            '--others',
            '--exclude-standard',
            '--',
            '.',
            ':(exclude)docs/dev_plan/**',
            ':(exclude)docs/task_reports/**',
        ], $resolved, 60, HanteiCheckEvidence::MAX_STREAM_BYTES);
        if (!$result->started() || 0 !== $result->exitCode() || null !== $result->blockedReason()) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::SubjectUnavailable, 'subject-unavailable');
        }

        $output = $result->stdout();
        if ('' === $output || !str_ends_with($output, "\0")) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::SubjectUnavailable, 'subject-unavailable');
        }
        $paths = explode("\0", substr($output, 0, -1));
        if (count($paths) > self::MAX_FILES || count($paths) !== count(array_unique($paths, SORT_STRING))) {
            throw new InvalidHanteiRequestException(HanteiDiagnostic::SubjectUnavailable, 'subject-unavailable');
        }
        usort($paths, strcmp(...));
        $manifest = '';
        $total = 0;
        foreach ($paths as $path) {
            if (!self::validRelativePath($path)) {
                throw new InvalidHanteiRequestException(HanteiDiagnostic::SubjectUnavailable, 'subject-unavailable');
            }
            $nativePath = $resolved . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            $realPath = realpath($nativePath);
            $before = @lstat($nativePath);
            if (false === $realPath || false === $before || is_link($nativePath) || !is_file($nativePath) || (($before['mode'] & 0170000) !== 0100000) || !self::contained($resolved, $realPath)) {
                throw new InvalidHanteiRequestException(HanteiDiagnostic::SubjectUnavailable, 'subject-unavailable');
            }
            $size = $before['size'];
            if ($size > self::MAX_FILE_BYTES) {
                throw new InvalidHanteiRequestException(HanteiDiagnostic::SubjectUnavailable, 'subject-unavailable');
            }
            $bytes = file_get_contents($nativePath);
            $after = @lstat($nativePath);
            if (false === $bytes || strlen($bytes) !== $size) {
                throw new InvalidHanteiRequestException(HanteiDiagnostic::SubjectUnavailable, 'subject-unavailable');
            }
            if (!self::sameStat($before, $after)) {
                throw new InvalidHanteiRequestException(HanteiDiagnostic::SubjectUnavailable, 'subject-unavailable');
            }
            $total += $size;
            if ($total > self::MAX_TOTAL_BYTES) {
                throw new InvalidHanteiRequestException(HanteiDiagnostic::SubjectUnavailable, 'subject-unavailable');
            }
            $manifest .= $path . "\0" . $size . "\0" . hash('sha256', $bytes) . "\n";
        }

        return new self($resolved, hash('sha256', $manifest), count($paths), $total);
    }

    public function root(): string
    {
        return $this->root;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }

    public function fileCount(): int
    {
        return $this->fileCount;
    }

    public function sourceBytes(): int
    {
        return $this->sourceBytes;
    }

    private static function validRelativePath(string $path): bool
    {
        return '' !== $path
            && 1 === preg_match('//u', $path)
            && !str_contains($path, '\\')
            && !str_starts_with($path, '/')
            && 1 !== preg_match('/\A[A-Za-z]:/', $path)
            && !in_array('..', explode('/', $path), true)
            && !str_contains($path, "\0")
            && !str_starts_with($path, 'docs/dev_plan/')
            && !str_starts_with($path, 'docs/task_reports/');
    }

    private static function contained(string $root, string $path): bool
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $path = str_replace('\\', '/', $path);
        if ('\\' === DIRECTORY_SEPARATOR) {
            $root = strtolower($root);
            $path = strtolower($path);
        }

        return str_starts_with($path, $root . '/');
    }

    /** @param array<int|string, int> $before */
    private static function sameStat(array $before, mixed $after): bool
    {
        if (!is_array($after)) {
            return false;
        }
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            $beforeValue = $before[$key] ?? null;
            $afterValue = $after[$key] ?? null;
            if (!is_int($beforeValue) || !is_int($afterValue) || $beforeValue !== $afterValue) {
                return false;
            }
        }

        return true;
    }
}
