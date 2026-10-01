<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterLocal\Exception\LocalStorageOperationException;
use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Profile\StorageProfileName;

/** Opens and completely validates one framed local record. */
#[Api(ApiStatus::Internal)]
final class LocalObjectReader
{
    public const string MARKER = "KONGONO-STORAGE-LOCAL/1\n";

    /** @return array{header: LocalObjectHeader, handle: resource, offset: int} */
    public static function open(string $path, string $namespace, string $logical, LocalPathLayout $layout, StorageProfileArtifact $artifact, StorageProfileName $profile): array
    {
        $layout->assertContained($path);
        if (!is_file($path) || is_link($path)) {
            throw LocalStorageOperationException::failed('unexpected_record_type', 'read');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw LocalStorageOperationException::failed('record_open_failed', 'read');
        }
        $complete = false;
        try {
            $layout->assertFileHandle($path, $handle);
            $marker = fread($handle, strlen(self::MARKER));
            $lengthLine = fgets($handle, 10);
            if ($marker !== self::MARKER || false === $lengthLine || 1 !== preg_match('/\A[0-9a-f]{8}\n\z/D', $lengthLine)) {
                throw LocalStorageOperationException::failed('record_framing', 'read');
            }
            $length = intval(substr($lengthLine, 0, 8), 16);
            if ($length < 2 || $length > CanonicalJson::MAX_BYTES) {
                throw LocalStorageOperationException::failed('header_length', 'read');
            }
            $headerBytes = self::readExact($handle, $length);
            $header = LocalObjectHeader::decode($headerBytes);
            $header->validate($namespace, $logical, $layout, $artifact, $profile);
            $offset = ftell($handle);
            if (false === $offset) {
                throw LocalStorageOperationException::failed('record_offset', 'read');
            }
            $hash = hash_init($header->digestAlgorithm());
            $remaining = $header->byteCount();
            while ($remaining > 0) {
                $readLength = self::positiveReadLength(min(65_536, $remaining));
                $bytes = fread($handle, $readLength);
                if (false === $bytes || '' === $bytes) {
                    throw LocalStorageOperationException::failed('record_truncated', 'read');
                }
                hash_update($hash, $bytes);
                $remaining -= strlen($bytes);
            }
            if ('' !== fread($handle, 1) || !hash_equals($header->digest(), hash_final($hash)) || 0 !== fseek($handle, $offset)) {
                throw LocalStorageOperationException::failed('record_integrity', 'read');
            }

            $layout->assertFileHandle($path, $handle);
            $complete = true;
            return ['header' => $header,'handle' => $handle,'offset' => $offset];
        } finally {
            if (!$complete && !fclose($handle)) {
                throw LocalStorageOperationException::cleanup('read');
            }
        }
    }

    /** @param resource $handle */
    private static function readExact(mixed $handle, int $length): string
    {
        $bytes = '';
        while (strlen($bytes) < $length) {
            $readLength = self::positiveReadLength($length - strlen($bytes));
            $part = fread($handle, $readLength);
            if (false === $part || '' === $part) {
                throw LocalStorageOperationException::failed('record_truncated', 'read');
            }
            $bytes .= $part;
        }

        return $bytes;
    }

    /** @return int<1, max> */
    private static function positiveReadLength(mixed $length): int
    {
        if (!is_int($length) || $length < 1) {
            throw LocalStorageOperationException::failed('record_length', 'read');
        }

        return $length;
    }

    private function __construct() {}
}
