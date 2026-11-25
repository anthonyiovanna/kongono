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
    /** @var list<string> */
    private const array FORBIDDEN_KEY_EXTENSIONS = [
        'php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'php8', 'pht', 'phps', 'inc',
        'html', 'htm', 'xhtml', 'xht', 'shtml', 'mhtml', 'svg', 'svgz', 'xml', 'xsl', 'xslt',
        'js', 'mjs', 'cjs', 'jsx', 'ts', 'tsx', 'jsp', 'asp', 'aspx', 'cgi', 'pl', 'py',
        'sh', 'bat', 'cmd', 'exe', 'ps1', 'vbs', 'hta', 'swf', 'jar',
    ];

    /** @var list<string> */
    private const array WINDOWS_RESERVED = [
        'con', 'prn', 'aux', 'nul',
        'com1', 'com2', 'com3', 'com4', 'com5', 'com6', 'com7', 'com8', 'com9',
        'lpt1', 'lpt2', 'lpt3', 'lpt4', 'lpt5', 'lpt6', 'lpt7', 'lpt8', 'lpt9',
    ];

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
            // Fix JSON > 4KB misclassified as text/plain when header truncation cut off closing brace
            if ('text/plain' === $detectedMime && $totalBytes > 4096 && $totalBytes <= $this->maxBytes) {
                $trimmedHead = ltrim($header, "\xEF\xBB\xBF \t\r\n");
                if ('' !== $trimmedHead && ('{' === $trimmedHead[0] || '[' === $trimmedHead[0])) {
                    rewind($temp);
                    $fullContent = stream_get_contents($temp);
                    if (is_string($fullContent) && self::looksLikeJson(ltrim($fullContent, "\xEF\xBB\xBF \t\r\n"))) {
                        $detectedMime = 'application/json';
                    }
                }
            }

            // Full-stream active-script & polyglot inspection across ALL file types (including PDF/ZIP/XML/images)
            rewind($temp);
            $activeScriptMime = self::scanStreamForActivePayload($temp, $detectedMime);
            if (null !== $activeScriptMime) {
                $detectedMime = $activeScriptMime;
            }

            if (!in_array($detectedMime, $this->allowedMimeTypes, true)) {
                throw new StorageException(sprintf(
                    'Uploaded file MIME type "%s" is not allowed (allowed: %s).',
                    $detectedMime,
                    implode(', ', $this->allowedMimeTypes),
                ));
            }

            $key = null !== $explicitKey && '' !== trim($explicitKey)
                ? self::validateExplicitKey(trim($explicitKey))
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
        $prefixActive = self::detectActivePayloadInWindow(substr($trimmed, 0, 1024), strictHtml: true);
        if (null !== $prefixActive) {
            return $prefixActive;
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

    /**
     * Stream-scan staged bytes across ALL MIME types (including PDF, ZIP, XML, images, and text)
     * in overlapping 64 KiB windows — including NUL-stripped views for UTF-16LE/BE payloads —
     * so disguised PHP/HTML/SVG/polyglot payloads are caught fail-closed.
     *
     * @param resource $stream
     */
    private static function scanStreamForActivePayload(mixed $stream, string $detectedMime): ?string
    {
        $strictHtml = str_starts_with($detectedMime, 'text/')
            || 'application/json' === $detectedMime
            || 'application/octet-stream' === $detectedMime
            || 'application/xml' === $detectedMime
            || 'text/xml' === $detectedMime
            || str_ends_with($detectedMime, '+xml');

        $overlap = '';
        while (!feof($stream)) {
            $chunk = fread($stream, 65536);
            if (false === $chunk || '' === $chunk) {
                break;
            }
            $rawWindow = $overlap . $chunk;
            $hit = self::detectActivePayloadInWindow($rawWindow, $strictHtml);
            if (null !== $hit) {
                return $hit;
            }
            if (str_contains($rawWindow, "\0")) {
                $noNulHit = self::detectActivePayloadInWindow(str_replace("\0", '', $rawWindow), strictHtml: false);
                if (null !== $noNulHit) {
                    return $noNulHit;
                }
            }
            $overlap = substr($rawWindow, -64);
        }

        return null;
    }

    private static function detectActivePayloadInWindow(string $window, bool $strictHtml): ?string
    {
        if (1 === preg_match('/<\?(?:php(?:\s|\/|$)|=[ \t\r\n\$"\'a-zA-Z_])/i', $window)) {
            return 'application/x-httpd-php';
        }
        if (1 === preg_match('/<svg(?:\s|\/|>)/i', $window)) {
            return 'image/svg+xml';
        }
        if (1 === preg_match('/<(?:script|iframe|object|embed|math|form|meta|style|link|details|marquee)(?:\s|\/|>)|<!doctype\s+html\b|<html(?:\s|\/|>)|<body(?:\s|\/|>)|<img(?:\s|\/)/i', $window)) {
            return 'text/html';
        }
        if ($strictHtml && 1 === preg_match('/<(?:video|audio|input)(?:\s|\/|>)|\bjavascript\s*:/i', $window)) {
            return 'text/html';
        }

        return null;
    }

    private static function validateExplicitKey(string $key): string
    {
        $normalized = ltrim(str_replace('\\', '/', $key), '/');
        if (
            '' === $normalized
            || str_contains($normalized, ':')
            || 1 === preg_match('/[\x00-\x1f\x7f]/', $normalized)
        ) {
            throw new StorageException(sprintf('Unsafe explicit upload storage key "%s".', $key));
        }

        $segments = explode('/', $normalized);
        foreach ($segments as $segment) {
            if (
                '' === $segment
                || '.' === $segment
                || '..' === $segment
                || str_contains($segment, '..')
                || str_starts_with($segment, '.')
                || str_ends_with($segment, '.')
                || str_ends_with($segment, ' ')
            ) {
                throw new StorageException(sprintf('Unsafe explicit upload storage key "%s".', $key));
            }
            $lowerSeg = strtolower($segment);
            $parts = explode('.', $lowerSeg);
            if (in_array($parts[0], self::WINDOWS_RESERVED, true)) {
                throw new StorageException(sprintf('Reserved device name in explicit upload key "%s".', $key));
            }
            // Inspect EVERY dot-separated sub-extension (blocks `shell.php.bak` and `shell.php.`)
            for ($idx = 1, $cnt = count($parts); $idx < $cnt; ++$idx) {
                if (in_array($parts[$idx], self::FORBIDDEN_KEY_EXTENSIONS, true)) {
                    throw new StorageException(sprintf('Dangerous executable/web file extension ".%s" is forbidden on upload key "%s".', $parts[$idx], $key));
                }
            }
        }

        return $normalized;
    }

    private static function looksLikeJson(string $trimmed): bool
    {
        if ('' === $trimmed || ('{' !== $trimmed[0] && '[' !== $trimmed[0])) {
            return false;
        }

        return json_validate($trimmed);
    }
}
