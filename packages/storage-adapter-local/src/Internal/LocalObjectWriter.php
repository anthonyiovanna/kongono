<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterLocal\Exception\LocalStorageOperationException;
use Kongono\StorageContract\Stream\StorageByteSource;

/** Streams one caller-owned source into one exclusive temporary record. */
#[Api(ApiStatus::Internal)]
final class LocalObjectWriter
{
    public static function write(string $path, LocalObjectHeader $header, StorageByteSource $source, int $chunkLimit): bool
    {
        $handle = @fopen($path, 'x+b');
        if (false === $handle) {
            throw LocalStorageOperationException::failed('temporary_create_failed', 'write');
        }
        $complete = false;
        try {
            $headerBytes = $header->encode();
            self::writeAll($handle, LocalObjectReader::MARKER . sprintf('%08x', strlen($headerBytes)) . "\n" . $headerBytes);
            $hash = hash_init($header->digestAlgorithm());
            $remaining = $header->byteCount();
            while ($remaining > 0) {
                $chunk = $source->read(min($chunkLimit, $remaining));
                if (null === $chunk) {
                    throw LocalStorageOperationException::failed('source_early_eof', 'write');
                }
                $bytes = $chunk->bytes();
                if (strlen($bytes) > $remaining || strlen($bytes) > $chunkLimit) {
                    throw LocalStorageOperationException::failed('source_chunk_bound', 'write');
                }
                self::writeAll($handle, $bytes);
                hash_update($hash, $bytes);
                $remaining -= strlen($bytes);
            }
            if (null !== $source->read($chunkLimit)) {
                throw LocalStorageOperationException::failed('source_extra_bytes', 'write');
            }
            if (!hash_equals($header->digest(), hash_final($hash))) {
                throw LocalStorageOperationException::failed('source_digest_mismatch', 'write');
            }
            if (!fflush($handle)) {
                throw LocalStorageOperationException::failed('temporary_flush_failed', 'write');
            }
            if (function_exists('fsync') && !fsync($handle)) {
                throw LocalStorageOperationException::failed('temporary_sync_failed', 'write');
            }
            if (!fclose($handle)) {
                throw LocalStorageOperationException::failed('temporary_close_failed', 'write');
            }
            $complete = true;

            return true;
        } finally {
            if (!$complete && is_resource($handle)) {
                @fclose($handle);
            }
        }
    }

    /** @param resource $handle */
    private static function writeAll(mixed $handle, string $bytes): void
    {
        $offset = 0;
        while ($offset < strlen($bytes)) {
            $written = fwrite($handle, substr($bytes, $offset));
            if (false === $written || 0 === $written) {
                throw LocalStorageOperationException::failed('temporary_write_failed', 'write');
            }
            $offset += $written;
        }
    }

    private function __construct() {}
}
