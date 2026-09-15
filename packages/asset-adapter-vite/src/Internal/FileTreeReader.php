<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Internal;

use FilesystemIterator;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterVite\Exception\ViteBuildExecutionException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** Reads one exact contained completed output tree with stable bounded bytes. */
#[Api(ApiStatus::Internal)]final class FileTreeReader
{
    public function readFile(string $path, int $maximumBytes): string
    {
        $resolved = realpath($path);
        if (false === $resolved || is_link($path) || !is_file($resolved) || PathGuard::normalize($resolved) !== PathGuard::normalize($path)) {
            throw ViteBuildExecutionException::failed('output_unsafe', output: true);
        }$before = @stat($resolved);
        if (false === $before || $before['size'] > $maximumBytes) {
            throw ViteBuildExecutionException::failed('output_bound', output: true);
        }$handle = @fopen($resolved, 'rb');
        if (false === $handle) {
            throw ViteBuildExecutionException::failed('output_unsafe', output: true);
        }$bytes = '';
        try {
            while (!feof($handle)) {
                $remaining = $maximumBytes + 1 - strlen($bytes);
                if ($remaining < 1) {
                    throw ViteBuildExecutionException::failed('output_bound', output: true);
                }
                $chunk = @fread($handle, min(8192, $remaining));
                if (false === $chunk) {
                    throw ViteBuildExecutionException::failed('output_unsafe', output: true);
                }$bytes .= $chunk;
                if (strlen($bytes) > $maximumBytes) {
                    throw ViteBuildExecutionException::failed('output_bound', output: true);
                }
            }$after = @fstat($handle);
        } finally {
            @fclose($handle);
        }if (false === $after || strlen($bytes) !== $before['size']) {
            throw ViteBuildExecutionException::failed('output_changed', output: true);
        }foreach (['dev','ino','mode','size','mtime','ctime'] as $key) {
            if ($before[$key] !== $after[$key]) {
                throw ViteBuildExecutionException::failed('output_changed', output: true);
            }
        }return$bytes;
    }
    /**
     * @param array<array-key, mixed> $expectedFiles
     *
     * @return array<string, string>
     */
    public function read(string $outputRoot, array $expectedFiles, int $maximumFiles, int $maximumFileBytes, int $maximumAggregateBytes): array
    {
        $root = realpath($outputRoot);
        if (false === $root || is_link($outputRoot) || !is_dir($root)) {
            throw ViteBuildExecutionException::failed('output_unsafe', output: true);
        }$root = PathGuard::normalize($root);
        $expected = [];
        foreach ($expectedFiles as $file) {
            if (!is_string($file)) {
                throw ViteBuildExecutionException::failed('output_inventory', output: true);
            }$expected[PathGuard::relative($file)] = true;
        }ksort($expected, SORT_STRING);
        if (count($expected) > $maximumFiles + 1) {
            throw ViteBuildExecutionException::failed('output_bound', output: true);
        }$actual = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                throw ViteBuildExecutionException::failed('output_unsafe', output: true);
            }
            $path = PathGuard::normalize($entry->getPathname());
            if (is_link($path) || false === ($resolved = realpath($path)) || PathGuard::normalize($resolved) !== $path || !PathGuard::contained($root, $path)) {
                throw ViteBuildExecutionException::failed('output_unsafe', output: true);
            }if ($entry->isDir()) {
                continue;
            }if (!$entry->isFile()) {
                throw ViteBuildExecutionException::failed('output_unsafe', output: true);
            }$relative = substr($path, strlen($root) + 1);
            PathGuard::relative($relative);
            $lower = strtolower($relative);
            if (isset($actual[$lower])) {
                throw ViteBuildExecutionException::failed('output_inventory', output: true);
            }$actual[$lower] = $relative;
        }sort($actual, SORT_STRING);
        $actualPaths = $actual;
        $expectedPaths = array_keys($expected);
        $actualOrdinal = $actualPaths;
        sort($actualOrdinal, SORT_STRING);
        if ($actualOrdinal !== $expectedPaths) {
            throw ViteBuildExecutionException::failed('output_inventory', output: true);
        }$result = [];
        $aggregate = 0;
        foreach ($expectedPaths as $relative) {
            $path = $root . '/' . $relative;
            $before = @stat($path);
            if (false === $before || $before['size'] > $maximumFileBytes) {
                throw ViteBuildExecutionException::failed('output_bound', output: true);
            }$handle = @fopen($path, 'rb');
            if (false === $handle) {
                throw ViteBuildExecutionException::failed('output_unsafe', output: true);
            }$bytes = '';
            try {
                while (!feof($handle)) {
                    $remaining = $maximumFileBytes + 1 - strlen($bytes);
                    if ($remaining < 1) {
                        throw ViteBuildExecutionException::failed('output_bound', output: true);
                    }
                    $chunk = @fread($handle, min(8192, $remaining));
                    if (false === $chunk) {
                        throw ViteBuildExecutionException::failed('output_unsafe', output: true);
                    }$bytes .= $chunk;
                    if (strlen($bytes) > $maximumFileBytes) {
                        throw ViteBuildExecutionException::failed('output_bound', output: true);
                    }
                }$after = @fstat($handle);
            } finally {
                @fclose($handle);
            }if (false === $after || strlen($bytes) !== $before['size']) {
                throw ViteBuildExecutionException::failed('output_changed', output: true);
            }foreach (['dev','ino','mode','size','mtime','ctime'] as $key) {
                if ($before[$key] !== $after[$key]) {
                    throw ViteBuildExecutionException::failed('output_changed', output: true);
                }
            }$aggregate += strlen($bytes);
            if ($aggregate > $maximumAggregateBytes) {
                throw ViteBuildExecutionException::failed('output_bound', output: true);
            }$result[$relative] = $bytes;
        }

        return $result;
    }
}
