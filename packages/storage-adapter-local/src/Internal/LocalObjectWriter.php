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
    /** @param resource $handle */
    public static function write(mixed $handle, LocalObjectHeader $header, StorageByteSource $source, int $chunkLimit): bool
    {
        $headerBytes = $header->encode();
        if (!self::writeAll($handle, LocalObjectReader::MARKER . sprintf('%08x', strlen($headerBytes)) . "\n" . $headerBytes)) {
            return false;
        }
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
            if (!self::writeAll($handle, $bytes)) {
                return false;
            }
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
            return false;
        }
        if (function_exists('fsync') && !fsync($handle)) {
            return false;
        }
        return true;
    }

    /** @param resource $handle */
    private static function writeAll(mixed $handle, string $bytes): bool
    {
        $offset = 0;
        while ($offset < strlen($bytes)) {
            $written = fwrite($handle, substr($bytes, $offset));
            if (false === $written || 0 === $written) {
                return false;
            }
            $offset += $written;
        }
        return true;
    }

    private function __construct() {}
}
