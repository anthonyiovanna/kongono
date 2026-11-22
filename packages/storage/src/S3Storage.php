<?php

declare(strict_types=1);

namespace Kongono\Storage;

use Closure;
use Kongono\Core\Diagnostics\SecretScrubber;
use SensitiveParameter;

/**
 * Pure PHP 8.5 S3-compatible object storage (AWS S3, Cloudflare R2, MinIO, Backblaze B2, Spaces).
 * Zero AWS SDK dependency; supports conditional writes (If-None-Match / If-Match) and presigned URLs.
 */
final readonly class S3Storage implements StorageInterface
{
    private const int MAX_RESPONSE_BYTES = 67_108_864;
    private const int MAX_RESPONSE_HEADERS = 256;

    private SigV4Signer $signer;
    private string $scheme;
    private string $host;

    /**
     * @param (Closure(string, string, array<string, string>, string): array{status: int, headers: array<string, string>, body: string})|null $httpSender
     */
    public function __construct(
        private string $bucket,
        string $accessKeyId,
        #[SensitiveParameter]
        string $secretAccessKey,
        string $region = 'us-east-1',
        ?string $endpoint = null,
        private ?Closure $httpSender = null,
    ) {
        $this->signer = new SigV4Signer($accessKeyId, $secretAccessKey, $region);
        $resolvedEndpoint = $endpoint ?? sprintf('https://s3.%s.amazonaws.com', $region);
        $parts = parse_url($resolvedEndpoint);
        if (false === $parts || !isset($parts['scheme'], $parts['host'])) {
            throw new StorageException(sprintf('Invalid S3 endpoint "%s".', $resolvedEndpoint));
        }
        $this->scheme = strtolower($parts['scheme']);
        if ('https' !== $this->scheme && 'http' !== $this->scheme) {
            throw new StorageException(sprintf('Unsupported S3 endpoint scheme "%s" (expected https or http).', $this->scheme));
        }
        if ('' === trim($bucket) || false !== strpbrk($bucket, "\r\n\0/\\")) {
            throw new StorageException('Invalid S3 bucket name.');
        }
        $this->host = strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    public function put(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        return $this->doPut($key, $contents, $contentType, $metadata, []);
    }

    public function putIfAbsent(string $key, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        return $this->doPut($key, $contents, $contentType, $metadata, ['if-none-match' => '*']);
    }

    public function replaceIfVersion(string $key, string $expectedVersion, string $contents, string $contentType = 'application/octet-stream', array $metadata = []): WriteResult
    {
        return $this->doPut($key, $contents, $contentType, $metadata, ['if-match' => $this->formatIfMatchEtag($expectedVersion)]);
    }

    public function get(string $key): ?string
    {
        $clean = $this->normalizeKey($key);
        $res = $this->send('GET', $this->objectUri($clean));
        if (404 === $res['status']) {
            return null;
        }
        if ($res['status'] < 200 || $res['status'] >= 300) {
            throw new StorageException(sprintf('S3 GET failed with HTTP %d.', $res['status']));
        }

        return $res['body'];
    }

    public function meta(string $key): ?ObjectMeta
    {
        $clean = $this->normalizeKey($key);
        $res = $this->send('HEAD', $this->objectUri($clean));
        if (404 === $res['status']) {
            return null;
        }
        if ($res['status'] < 200 || $res['status'] >= 300) {
            throw new StorageException(sprintf('S3 HEAD failed with HTTP %d.', $res['status']));
        }

        $etag = trim($res['headers']['etag'] ?? '', '"');
        $size = is_numeric($res['headers']['content-length'] ?? null) ? (int) $res['headers']['content-length'] : 0;

        return new ObjectMeta(
            key: $clean,
            size: $size,
            sha256: $res['headers']['x-amz-checksum-sha256'] ?? '',
            version: $etag,
            contentType: $res['headers']['content-type'] ?? 'application/octet-stream',
            updatedAt: $res['headers']['last-modified'] ?? gmdate(DATE_ATOM),
        );
    }

    public function exists(string $key): bool
    {
        return null !== $this->meta($key);
    }

    public function delete(string $key, ?string $expectedVersion = null): StorageOutcome
    {
        $clean = $this->normalizeKey($key);
        $extra = null !== $expectedVersion ? ['if-match' => $this->formatIfMatchEtag($expectedVersion)] : [];
        $res = $this->send('DELETE', $this->objectUri($clean), [], $extra);

        return match (true) {
            404 === $res['status'] => StorageOutcome::Absent,
            412 === $res['status'] => StorageOutcome::Conflict,
            $res['status'] >= 200 && $res['status'] < 300 => StorageOutcome::Deleted,
            default => StorageOutcome::Uncertain,
        };
    }

    public function list(string $prefix = ''): array
    {
        $query = ['list-type' => '2'];
        $cleanPrefix = ltrim(str_replace('\\', '/', trim($prefix)), '/');
        if ('' !== $cleanPrefix) {
            $query['prefix'] = $cleanPrefix;
        }

        $res = $this->send('GET', '/' . rawurlencode($this->bucket), $query);
        if ($res['status'] < 200 || $res['status'] >= 300) {
            throw new StorageException(sprintf('S3 ListObjectsV2 failed with HTTP %d.', $res['status']));
        }

        $objects = [];
        // Defend against XML Entity Expansion / XXE payloads (including UTF-16/UTF-32 encoded DTDs)
        $body = $res['body'];
        if (
            str_contains($body, "\0")
            || str_starts_with($body, "\xFF\xFE")
            || str_starts_with($body, "\xFE\xFF")
            || 1 === preg_match('/<!(?:DOCTYPE|ENTITY)\b/i', $body)
        ) {
            throw new StorageException('Unsafe XML DOCTYPE/ENTITY declaration rejected in S3 ListObjectsV2 response.');
        }
        $dom = new \DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $loaded = '' !== trim($body) && $dom->loadXML($body, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);
        if ($loaded) {
            if (null !== $dom->doctype) {
                throw new StorageException('Unsafe XML DOCTYPE/ENTITY declaration rejected in S3 ListObjectsV2 response.');
            }
            $xml = simplexml_import_dom($dom);
            if (null !== $xml && isset($xml->Contents)) {
                foreach ($xml->Contents as $item) {
                    $objects[] = new ObjectMeta(
                        key: (string) $item->Key,
                        size: (int) $item->Size,
                        sha256: '',
                        version: trim((string) $item->ETag, '"'),
                        contentType: 'application/octet-stream',
                        updatedAt: (string) $item->LastModified,
                    );
                }
            }
        }

        return $objects;
    }

    /** Generate a presigned GET download URL for direct client downloads. */
    public function presignedGetUrl(string $key, int $expiresSeconds = 900, ?int $timestamp = null): string
    {
        return $this->signer->presignUrl('GET', $this->scheme, $this->host, $this->objectUri($this->normalizeKey($key)), $expiresSeconds, $timestamp);
    }

    /** Generate a presigned PUT upload URL for direct client uploads. */
    public function presignedPutUrl(string $key, int $expiresSeconds = 900, ?int $timestamp = null): string
    {
        return $this->signer->presignUrl('PUT', $this->scheme, $this->host, $this->objectUri($this->normalizeKey($key)), $expiresSeconds, $timestamp);
    }

    /**
     * @param array<string, string> $metadata
     * @param array<string, string> $conditionalHeaders
     */
    private function doPut(string $key, string $contents, string $contentType, array $metadata, array $conditionalHeaders): WriteResult
    {
        $clean = $this->normalizeKey($key);
        $headers = ['content-type' => $contentType, ...$conditionalHeaders];
        foreach ($metadata as $k => $v) {
            $headers['x-amz-meta-' . strtolower($k)] = $v;
        }

        $res = $this->send('PUT', $this->objectUri($clean), [], $headers, $contents);
        if (412 === $res['status']) {
            $outcome = isset($conditionalHeaders['if-none-match']) ? StorageOutcome::AlreadyExists : StorageOutcome::Conflict;

            return new WriteResult($outcome);
        }
        if ($res['status'] < 200 || $res['status'] >= 300) {
            throw new StorageException(sprintf('S3 PUT failed with HTTP %d: %s', $res['status'], SecretScrubber::scrub(substr($res['body'], 0, 200))));
        }

        $etag = trim($res['headers']['etag'] ?? hash('md5', $contents), '"');
        $meta = new ObjectMeta(
            key: $clean,
            size: strlen($contents),
            sha256: hash('sha256', $contents),
            version: $etag,
            contentType: $contentType,
            updatedAt: gmdate(DATE_ATOM),
            metadata: $metadata,
        );

        return new WriteResult(isset($conditionalHeaders['if-match']) ? StorageOutcome::Replaced : StorageOutcome::Created, $meta);
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function send(string $method, string $canonicalUri, array $query = [], array $headers = [], string $body = ''): array
    {
        $payloadHash = hash('sha256', $body);
        $signedHeaders = $this->signer->signHeaders($method, $this->host, $canonicalUri, $query, $headers, $payloadHash);
        $url = sprintf('%s://%s%s', $this->scheme, $this->host, $canonicalUri);
        if ([] !== $query) {
            ksort($query, SORT_STRING);
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        if (null !== $this->httpSender) {
            return ($this->httpSender)($method, $url, $signedHeaders, $body);
        }

        return $this->sendCurl($method, $url, $signedHeaders, $body);
    }

    /**
     * @param array<string, string> $headers
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function sendCurl(string $method, string $url, array $headers, string $body): array
    {
        $upper = strtoupper(trim($method));
        if ('' === $upper) {
            throw new StorageException('S3 HTTP method cannot be empty.');
        }
        $ch = curl_init($url);
        if (false === $ch) {
            throw new StorageException('Unable to initialize cURL for S3.');
        }

        $headerLines = [];
        foreach ($headers as $k => $v) {
            if (1 !== preg_match('/\A[a-zA-Z0-9!#$%&\'*+.^_`|~-]+\z/', $k) || false !== strpbrk($v, "\r\n\0")) {
                throw new StorageException(sprintf('Unsafe S3 header "%s" rejected.', $k));
            }
            $headerLines[] = $k . ': ' . $v;
        }

        $respHeaders = [];
        $respBody = '';
        $overflowed = false;
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $upper,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PATH_AS_IS => true,
            CURLOPT_PROTOCOLS_STR => $this->scheme,
            CURLOPT_REDIR_PROTOCOLS_STR => $this->scheme,
            CURLOPT_NOBODY => 'HEAD' === $upper,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADERFUNCTION => static function (mixed $_ch, string $line) use (&$respHeaders): int {
                $parts = explode(':', $line, 2);
                if (2 === count($parts) && count($respHeaders) < self::MAX_RESPONSE_HEADERS) {
                    $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => static function (mixed $_ch, string $chunk) use (&$respBody, &$overflowed): int {
                if (strlen($respBody) + strlen($chunk) > self::MAX_RESPONSE_BYTES) {
                    $overflowed = true;

                    return 0;
                }
                $respBody .= $chunk;

                return strlen($chunk);
            },
        ]);

        if ('' !== $body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $ok = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);

        if ($overflowed) {
            throw new StorageException(sprintf('S3 response exceeded maximum bound of %d bytes.', self::MAX_RESPONSE_BYTES));
        }
        if (false === $ok) {
            throw new StorageException('S3 transport failed: ' . SecretScrubber::scrub($err));
        }

        return ['status' => $status, 'headers' => $respHeaders, 'body' => $respBody];
    }

    private function formatIfMatchEtag(string $expectedVersion): string
    {
        $clean = trim($expectedVersion, "\" \t");
        if ('' === $clean || 1 !== preg_match('/\A[A-Za-z0-9._\-:\/+=]{1,128}\z/', $clean)) {
            throw new StorageException('Invalid S3 ETag version token for If-Match.');
        }

        return '"' . $clean . '"';
    }

    private function normalizeKey(string $key): string
    {
        if (1 === preg_match('/[\x00-\x1f\x7f]/', $key)) {
            throw new StorageException(sprintf('Invalid storage key "%s".', $key));
        }
        $clean = ltrim(str_replace('\\', '/', trim($key)), '/');
        if ('' === $clean) {
            throw new StorageException(sprintf('Invalid storage key "%s".', $key));
        }
        foreach (explode('/', $clean) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                throw new StorageException(sprintf('Invalid storage key "%s".', $key));
            }
        }

        return $clean;
    }

    private function objectUri(string $cleanKey): string
    {
        $encodedSegments = array_map('rawurlencode', explode('/', $cleanKey));

        return '/' . rawurlencode($this->bucket) . '/' . implode('/', $encodedSegments);
    }
}
