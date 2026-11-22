<?php

declare(strict_types=1);

namespace Kongono\Storage;

use SensitiveParameter;

/** Pure PHP 8.5 AWS Signature Version 4 signer (for AWS S3, Cloudflare R2, MinIO, B2, Spaces). */
final readonly class SigV4Signer
{
    public function __construct(
        private string $accessKeyId,
        #[SensitiveParameter]
        private string $secretAccessKey,
        private string $region = 'us-east-1',
        private string $service = 's3',
    ) {
        if (1 !== preg_match('/\A[A-Za-z0-9._\-+=]{1,128}\z/', $accessKeyId)) {
            throw new StorageException('Invalid S3 accessKeyId format.');
        }
        if (1 !== preg_match('/\A[a-z0-9][a-z0-9-]{0,62}\z/i', $region)) {
            throw new StorageException(sprintf('Invalid S3 region "%s".', $region));
        }
        if (1 !== preg_match('/\A[a-z0-9-]{1,64}\z/i', $service)) {
            throw new StorageException(sprintf('Invalid SigV4 service name "%s".', $service));
        }
    }

    /**
     * Sign an S3 HTTP request and return the complete signed header map.
     *
     * @param array<string, string> $headers
     * @param array<string, string> $query
     * @return array<string, string>
     */
    public function signHeaders(
        string $method,
        string $host,
        string $canonicalUri,
        array $query = [],
        array $headers = [],
        string $payloadHash = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
        ?int $timestamp = null,
    ): array {
        $ts = $timestamp ?? time();
        $amzDate = gmdate('Ymd\THis\Z', $ts);
        $dateStamp = gmdate('Ymd', $ts);

        $lowerHeaders = [];
        foreach ($headers as $k => $v) {
            $name = strtolower(trim($k));
            $val = trim($v);
            if (1 !== preg_match('/\A[a-z0-9!#$%&\'*+.^_`|~-]+\z/', $name) || false !== strpbrk($val, "\r\n\0")) {
                throw new StorageException(sprintf('Unsafe header "%s" rejected by SigV4Signer.', $k));
            }
            $lowerHeaders[$name] = $val;
        }
        self::assertSafeMethod($method);
        self::assertSafeHost($host);
        self::assertSafeCanonicalUri($canonicalUri);
        $lowerHeaders['host'] = $host;
        $lowerHeaders['x-amz-date'] = $amzDate;
        $lowerHeaders['x-amz-content-sha256'] = $payloadHash;
        ksort($lowerHeaders, SORT_STRING);

        $canonicalHeaders = '';
        foreach ($lowerHeaders as $k => $v) {
            $canonicalHeaders .= $k . ':' . $v . "\n";
        }
        $signedHeaders = implode(';', array_keys($lowerHeaders));
        $canonicalQuery = $this->canonicalQueryString($query);

        $canonicalRequest = implode("\n", [
            strtoupper($method),
            $canonicalUri,
            $canonicalQuery,
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $scope = sprintf('%s/%s/%s/aws4_request', $dateStamp, $this->region, $this->service);
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($dateStamp));
        $lowerHeaders['authorization'] = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $this->accessKeyId,
            $scope,
            $signedHeaders,
            $signature,
        );

        return $lowerHeaders;
    }

    /** Generate a SigV4 presigned URL (valid for $expiresSeconds, default 15 minutes). */
    public function presignUrl(
        string $method,
        string $scheme,
        string $host,
        string $canonicalUri,
        int $expiresSeconds = 900,
        ?int $timestamp = null,
    ): string {
        self::assertSafeMethod($method);
        $cleanScheme = strtolower(trim($scheme));
        if ('https' !== $cleanScheme && 'http' !== $cleanScheme) {
            throw new StorageException(sprintf('Unsupported presigned URL scheme "%s".', $scheme));
        }
        self::assertSafeHost($host);
        self::assertSafeCanonicalUri($canonicalUri);

        $ts = $timestamp ?? time();
        $amzDate = gmdate('Ymd\THis\Z', $ts);
        $dateStamp = gmdate('Ymd', $ts);
        $scope = sprintf('%s/%s/%s/aws4_request', $dateStamp, $this->region, $this->service);

        $query = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $this->accessKeyId . '/' . $scope,
            'X-Amz-Date' => $amzDate,
            'X-Amz-Expires' => (string) max(1, min(604800, $expiresSeconds)),
            'X-Amz-SignedHeaders' => 'host',
        ];

        $canonicalQuery = $this->canonicalQueryString($query);
        $canonicalRequest = implode("\n", [
            strtoupper($method),
            $canonicalUri,
            $canonicalQuery,
            'host:' . $host . "\n",
            'host',
            'UNSIGNED-PAYLOAD',
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($dateStamp));

        return sprintf('%s://%s%s?%s&X-Amz-Signature=%s', $cleanScheme, $host, $canonicalUri, $canonicalQuery, $signature);
    }

    private static function assertSafeMethod(string $method): void
    {
        if (1 !== preg_match('/\A[A-Za-z]{1,16}\z/', $method)) {
            throw new StorageException(sprintf('Unsafe HTTP method "%s" rejected by SigV4Signer.', $method));
        }
    }

    private static function assertSafeHost(string $host): void
    {
        if ('' === trim($host) || false !== strpbrk($host, " \t\r\n\0/?#@\\") || 1 !== preg_match('/\A(?:\[[0-9a-fA-F:]+\]|[a-zA-Z0-9.-]+)(?::\d{1,5})?\z/', $host)) {
            throw new StorageException('Unsafe host rejected by SigV4Signer.');
        }
    }

    private static function assertSafeCanonicalUri(string $uri): void
    {
        if ('' === $uri || !str_starts_with($uri, '/') || 1 === preg_match('/[\x00-\x1f\x7f\s?#]/', $uri)) {
            throw new StorageException('Unsafe canonical URI rejected by SigV4Signer.');
        }
    }

    private function signingKey(string $dateStamp): string
    {
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretAccessKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $this->service, $kRegion, true);

        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }

    /** @param array<string, string> $query */
    private function canonicalQueryString(array $query): string
    {
        if ([] === $query) {
            return '';
        }
        ksort($query, SORT_STRING);
        $pairs = [];
        foreach ($query as $k => $v) {
            $pairs[] = rawurlencode($k) . '=' . rawurlencode($v);
        }

        return implode('&', $pairs);
    }
}
