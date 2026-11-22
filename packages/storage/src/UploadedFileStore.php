<?php

declare(strict_types=1);

namespace Kongono\Storage;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Security-hardened uploaded-file ingestion pipeline for `StorageInterface`.
 *
 * Guarantees:
 *   - Never trusts client-supplied `Content-Type` headers or filename extensions
 *   - Inspects actual magic bytes (pure PHP signatures + optional `finfo` fallback)
 *   - Actively classifies disguised PHP, HTML, and SVG payloads to prevent stored XSS/RCE
 *   - Streams directly into `StorageInterface::putStream()` with hard byte-size enforcement
 *   - Generates collision-free random storage keys with extensions derived strictly from sniffed MIME
 */
final readonly class UploadedFileStore
{
    /** @var array<string, string> */
    public const array MIME_EXTENSIONS = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
        'application/json' => 'json',
        'application/zip' => 'zip',
        'application/gzip' => 'gz',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'audio/mpeg' => 'mp3',
    ];

    /**
     * @param list<string> $allowedMimeTypes Allowlist of true sniffed MIME types
     * @param int          $maxBytes         Hard maximum size in bytes (default 10 MB)
     */
    public function __construct(
        private StorageInterface $storage,
        private array $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/webp', 'application/pdf'],
        private int $maxBytes = 10_485_760,
    ) {
        if ($this->maxBytes < 1) {
            throw new StorageException('UploadedFileStore maxBytes must be >= 1.');
        }
    }

    /**
     * Validate and stream a PSR-7 uploaded file into storage.
     *
     * @param array<string, string> $metadata Additional object metadata
     */
    public function store(
        UploadedFileInterface $file,
        string $prefix = 'uploads',
        ?string $explicitKey = null,
        array $metadata = [],
    ): WriteResult {
        if (UPLOAD_ERR_OK !== $file->getError()) {
            throw new StorageException(sprintf('Uploaded file failed with PHP upload error code %d.', $file->getError()));
        }

        $declaredSize = $file->getSize();
        if (null !== $declaredSize && $declaredSize > $this->maxBytes) {
            throw new StorageException(sprintf('Uploaded file exceeds maximum allowed size of %d bytes.', $this->maxBytes));
        }

        $stream = $file->getStream();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $temp = fopen('php://temp/maxmemory:2097152', 'r+b');
        if (false === $temp) {
            throw new StorageException('Unable to open temporary staging stream for upload.');
        }

        $totalBytes = 0;
        $header = '';

        try {
            while (!$stream->eof()) {
                $chunk = $stream->read(65536);
                if ('' === $chunk) {
                    break;
                }
                $totalBytes += strlen($chunk);
                if ($totalBytes > $this->maxBytes) {
                    throw new StorageException(sprintf('Uploaded file exceeds maximum allowed size of %d bytes.', $this->maxBytes));
                }
                if (strlen($header) < 4096) {
                    $header .= substr($chunk, 0, 4096 - strlen($header));
                }
                $written = fwrite($temp, $chunk);
                if (false === $written || $written !== strlen($chunk)) {
                    throw new StorageException('Failed to stage uploaded file stream.');
                }
            }

            if (0 === $totalBytes) {
                throw new StorageException('Uploaded file cannot be empty.');
            }

            $detectedMime = self::detectMimeType($header);
            if (!in_array($detectedMime, $this->allowedMimeTypes, true)) {
                throw new StorageException(sprintf(
                    'Uploaded file MIME type "%s" is not allowed (allowed: %s).',
                    $detectedMime,
                    implode(', ', $this->allowedMimeTypes),
                ));
            }

            $key = null !== $explicitKey && '' !== trim($explicitKey)
                ? trim($explicitKey)
                : $this->generateSafeKey($prefix, $detectedMime);

            $mergedMeta = $metadata;
            $mergedMeta['detected_mime'] = $detectedMime;
            $clientFilename = $file->getClientFilename();
            if (null !== $clientFilename && '' !== trim($clientFilename)) {
                $safeOrig = trim((string) preg_replace('/[\x00-\x1f\x7f\/\\\\]+/', '_', basename($clientFilename)));
                if ('' !== $safeOrig) {
                    $mergedMeta['original_filename'] = substr($safeOrig, 0, 120);
                }
            }

            rewind($temp);

            return $this->storage->putStream($key, $temp, $detectedMime, $mergedMeta);
        } finally {
            fclose($temp);
        }
    }

    /**
     * Detect MIME type from actual file header bytes (magic-byte signatures first, optional finfo fallback).
     */
    public static function detectMimeType(string $headerBytes): string
    {
        if ('' === $headerBytes) {
            return 'application/octet-stream';
        }

        // Binary magic-byte signatures (deterministic even without ext-fileinfo)
        if (str_starts_with($headerBytes, "\x89PNG\r\n\x1A\n")) {
            return 'image/png';
        }
        if (str_starts_with($headerBytes, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($headerBytes, 'GIF87a') || str_starts_with($headerBytes, 'GIF89a')) {
            return 'image/gif';
        }
        if (strlen($headerBytes) >= 12 && str_starts_with($headerBytes, 'RIFF') && 'WEBP' === substr($headerBytes, 8, 4)) {
            return 'image/webp';
        }
        if (str_starts_with($headerBytes, '%PDF-')) {
            return 'application/pdf';
        }
        if (str_starts_with($headerBytes, "PK\x03\x04") || str_starts_with($headerBytes, "PK\x05\x06")) {
            return 'application/zip';
        }
        if (str_starts_with($headerBytes, "\x1F\x8B")) {
            return 'application/gzip';
        }
        if (strlen($headerBytes) >= 8 && 'ftyp' === substr($headerBytes, 4, 4)) {
            return 'video/mp4';
        }
        if (str_starts_with($headerBytes, "\x1A\x45\xDF\xA3")) {
            return 'video/webm';
        }
        if (str_starts_with($headerBytes, 'ID3') || str_starts_with($headerBytes, "\xFF\xFB") || str_starts_with($headerBytes, "\xFF\xF3")) {
            return 'audio/mpeg';
        }

        // Actively detect disguised executable/markup scripts so they cannot masquerade as plain text
        $trimmed = ltrim($headerBytes, "\xEF\xBB\xBF \t\r\n");
        $lowerPrefix = strtolower(substr($trimmed, 0, 512));
        if (str_contains($lowerPrefix, '<?php') || str_contains($lowerPrefix, '<?=')) {
            return 'application/x-httpd-php';
        }
        if (str_contains($lowerPrefix, '<svg')) {
            return 'image/svg+xml';
        }
        if (
            str_starts_with($lowerPrefix, '<!doctype html')
            || str_contains($lowerPrefix, '<html')
            || str_contains($lowerPrefix, '<script')
        ) {
            return 'text/html';
        }

        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->buffer($headerBytes);
            if (is_string($detected) && '' !== $detected) {
                $clean = strtolower(trim(explode(';', $detected, 2)[0]));
                if ('text/plain' === $clean && self::looksLikeJson($trimmed)) {
                    return 'application/json';
                }

                return $clean;
            }
        }

        if (self::looksLikeJson($trimmed)) {
            return 'application/json';
        }
        if (!str_contains($headerBytes, "\0") && mb_check_encoding($headerBytes, 'UTF-8')) {
            return 'text/plain';
        }

        return 'application/octet-stream';
    }

    private function generateSafeKey(string $prefix, string $mimeType): string
    {
        $cleanPrefix = trim(str_replace('\\', '/', $prefix), '/');
        if ('' === $cleanPrefix) {
            $cleanPrefix = 'uploads';
        }
        foreach (explode('/', $cleanPrefix) as $seg) {
            if ('' === $seg || '.' === $seg || '..' === $seg || 1 !== preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/', $seg)) {
                throw new StorageException(sprintf('Invalid upload prefix "%s".', $prefix));
            }
        }

        $ext = self::MIME_EXTENSIONS[$mimeType] ?? 'bin';

        return sprintf('%s/%s/%s.%s', $cleanPrefix, gmdate('Y/m'), bin2hex(random_bytes(16)), $ext);
    }

    private static function looksLikeJson(string $trimmed): bool
    {
        if ('' === $trimmed || ('{' !== $trimmed[0] && '[' !== $trimmed[0])) {
            return false;
        }
        json_decode($trimmed);

        return JSON_ERROR_NONE === json_last_error();
    }
}
