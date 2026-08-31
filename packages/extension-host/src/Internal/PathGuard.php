<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionHost\Exception\InvalidGeneratedExtensionHostException;

/** Admits one canonical local ordinary file through a bounded stable metadata snapshot. */
#[Api(ApiStatus::Internal)]
final class PathGuard
{
    /** @return array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} */
    public static function readGeneratedFile(string $path, int $limit): array
    {
        if ('' === $path || str_contains($path, "\0") || self::hasStreamSyntax($path) || !self::isAbsolute($path)) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_path_invalid');
        }

        $resolved = realpath($path);
        $normalizedInput = self::normalize($path);
        if (false === $resolved || self::normalize($resolved) !== $normalizedInput || is_link($path) || !is_file($path)) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_path_invalid');
        }
        self::assertNoAliasChain($normalizedInput);

        $before = @stat($path);
        if (false === $before || $before['size'] < 0 || $before['size'] > $limit) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_path_invalid');
        }

        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_path_invalid');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $remaining = $limit + 1 - strlen($bytes);
                if ($remaining < 1) {
                    throw InvalidGeneratedExtensionHostException::invalid('generated_path_invalid');
                }
                $chunk = fread($handle, min(8192, $remaining));
                if (false === $chunk) {
                    throw InvalidGeneratedExtensionHostException::invalid('generated_path_invalid');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > $limit) {
                    throw InvalidGeneratedExtensionHostException::invalid('generated_path_invalid');
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }

        if (false === $after || !self::sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_file_changed');
        }

        return [
            'absolute' => $normalizedInput,
            'bytes' => $bytes,
            'size' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
            'stat' => $before,
        ];
    }

    /** @param array{absolute:string,bytes:string,size:int,sha256:string,stat:array<string|int, mixed>} $snapshot */
    public static function reverify(array $snapshot): void
    {
        $stat = @stat($snapshot['absolute']);
        if (false === $stat || !self::sameMetadata($snapshot['stat'], $stat)) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_file_changed');
        }
        $bytes = @file_get_contents($snapshot['absolute']);
        if (false === $bytes || strlen($bytes) !== $snapshot['size'] || !hash_equals($snapshot['sha256'], hash('sha256', $bytes))) {
            throw InvalidGeneratedExtensionHostException::invalid('generated_file_changed');
        }
    }

    /**
     * @param array<string|int, mixed> $before
     * @param array<string|int, mixed> $after
     */
    private static function sameMetadata(array $before, array $after): bool
    {
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private static function assertNoAliasChain(string $path): void
    {
        $drive = 1 === preg_match('/\A[A-Za-z]:\//D', $path) ? substr($path, 0, 2) : '';
        $rest = '' !== $drive ? substr($path, 3) : ltrim($path, '/');
        $prefix = '' !== $drive ? $drive . '/' : '/';
        foreach (explode('/', $rest) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw InvalidGeneratedExtensionHostException::invalid('generated_path_invalid');
            }
            $prefix = rtrim($prefix, '/') . '/' . $segment;
            $resolved = realpath($prefix);
            $linkTarget = @readlink($prefix);
            if (
                is_link($prefix)
                || (is_string($linkTarget) && self::normalize($linkTarget) !== $prefix)
                || (false !== $resolved && self::normalize($resolved) !== $prefix)
            ) {
                throw InvalidGeneratedExtensionHostException::invalid('generated_path_invalid');
            }
        }
    }

    private static function isAbsolute(string $path): bool
    {
        $normalized = self::normalize($path);

        return 1 === preg_match('/\A[A-Za-z]:\//D', $normalized) || str_starts_with($normalized, '/');
    }

    private static function hasStreamSyntax(string $path): bool
    {
        $normalized = self::normalize($path);
        $withoutDrive = 1 === preg_match('/\A[A-Za-z]:\//D', $normalized) ? substr($normalized, 2) : $normalized;

        return str_contains($withoutDrive, ':') || str_starts_with($normalized, '//');
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function __construct() {}
}
