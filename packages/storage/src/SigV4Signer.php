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
    ) {}

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
            $lowerHeaders[strtolower(trim($k))] = trim($v);
        }
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

        return sprintf('%s://%s%s?%s&X-Amz-Signature=%s', $scheme, $host, $canonicalUri, $canonicalQuery, $signature);
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
