<?php

declare(strict_types=1);

namespace Kongono\Storage;

use SensitiveParameter;

/**
 * HKDF-SHA256 domain-separated signed temporary URL generator & verifier for `LocalStorage`
 * (or any `StorageInterface` served through a Kongono download route).
 *
 * Uses strict canonical query parsing immune to `parse_str` duplicate-parameter (HPP) smuggling,
 * constant-time signature verification, and automatic expiry enforcement.
 */
final readonly class SignedLocalUrl
{
    private const int MAX_URL_BYTES = 4096;
    private const int MAX_QUERY_PARAMS = 32;

    private string $signingKey;
    private string $basePath;

    public function __construct(
        #[SensitiveParameter]
        string $secretKey,
        string $basePath = '/_storage/download',
        string $domain = 'kongono:storage-signed-url:v1',
    ) {
        if (strlen($secretKey) < 16) {
            throw new StorageException('SignedLocalUrl secret key must be at least 16 bytes.');
        }

        $cleanBase = trim($basePath);
        if ('' === $cleanBase || 1 === preg_match('/[\x00-\x1f\x7f?#]/', $cleanBase)) {
            throw new StorageException('Invalid SignedLocalUrl base path.');
        }
        $parts = parse_url($cleanBase);
        if (false === $parts || isset($parts['user']) || isset($parts['pass'])) {
            throw new StorageException('Invalid SignedLocalUrl base path.');
        }
        if (isset($parts['scheme']) !== isset($parts['host'])) {
            throw new StorageException('SignedLocalUrl base path must provide both scheme and host, or neither.');
        }
        if (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            throw new StorageException(sprintf('Unsupported SignedLocalUrl scheme "%s".', $parts['scheme']));
        }

        $this->signingKey = hash_hkdf('sha256', $secretKey, 32, $domain);
        $this->basePath = $this->canonicalBase($parts);
    }

    /** Generate a temporary signed download URL for `$key` valid for `$expiresSeconds` (default 15 minutes). */
    public function temporaryDownloadUrl(
        string $key,
        int $expiresSeconds = 900,
        ?string $downloadName = null,
        ?string $disk = null,
        ?int $now = null,
    ): string {
        $cleanKey = $this->validateKey($key);
        $params = [
            'expires' => (string) (($now ?? time()) + max(1, min(604_800, $expiresSeconds))),
            'key' => $cleanKey,
        ];
        if (null !== $disk && '' !== trim($disk)) {
            $cleanDisk = strtolower(trim($disk));
            if (1 !== preg_match('/\A[a-z0-9][a-z0-9_-]{0,63}\z/', $cleanDisk)) {
                throw new StorageException(sprintf('Invalid storage disk name "%s".', $disk));
            }
            $params['disk'] = $cleanDisk;
        }
        if (null !== $downloadName && '' !== trim($downloadName)) {
            $cleanName = trim($downloadName);
            if (1 === preg_match('/[\x00-\x1f\x7f\/\\\\]/', $cleanName)) {
                throw new StorageException('Invalid download filename.');
            }
            $params['name'] = $cleanName;
        }

        ksort($params, SORT_STRING);
        $canonicalQuery = $this->encodeQuery($params);
        $signature = hash_hmac('sha256', $this->basePath . '?' . $canonicalQuery, $this->signingKey);
        $params['signature'] = $signature;

        return $this->basePath . '?' . $this->encodeQuery($params);
    }

    /**
     * Verify a signed download URL (full URL, path+query, or Request URI) and return its verified payload,
     * or `null` when tampered, expired, or malformed.
     *
     * @return array{key: string, disk: ?string, name: ?string, expires: int}|null
     */
    public function verify(string $signedUrl, ?int $now = null): ?array
    {
        if ('' === $signedUrl || strlen($signedUrl) > self::MAX_URL_BYTES || 1 === preg_match('/[\x00-\x1f\x7f]/', $signedUrl)) {
            return null;
        }

        $parts = parse_url($signedUrl);
        if (
            false === $parts
            || !isset($parts['path'], $parts['query'])
            || isset($parts['fragment'])
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            return null;
        }

        $target = $this->canonicalBase($parts);
        if ($target !== $this->basePath) {
            // Allow verifying path-only request URIs against full-origin base paths only if exact paths match
            $baseParts = parse_url($this->basePath);
            $expectedPath = is_array($baseParts) && is_string($baseParts['path'] ?? null) ? $baseParts['path'] : $this->basePath;
            if (isset($parts['scheme']) || isset($parts['host']) || $parts['path'] !== $expectedPath) {
                return null;
            }
            $target = $this->basePath;
        }

        $params = self::parseStrictQuery($parts['query']);
        if (null === $params) {
            return null;
        }

        $signature = $params['signature'] ?? null;
        $expiresRaw = $params['expires'] ?? null;
        $key = $params['key'] ?? null;
        if (
            !is_string($signature) || 64 !== strlen($signature) || !ctype_xdigit($signature)
            || !is_string($expiresRaw) || !ctype_digit($expiresRaw)
            || !is_string($key) || '' === $key
        ) {
            return null;
        }

        $expires = (int) $expiresRaw;
        if (($now ?? time()) >= $expires) {
            return null;
        }

        unset($params['signature']);
        ksort($params, SORT_STRING);
        $expected = hash_hmac('sha256', $target . '?' . $this->encodeQuery($params), $this->signingKey);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        try {
            $cleanKey = $this->validateKey($key);
        } catch (StorageException) {
            return null;
        }

        return [
            'key' => $cleanKey,
            'disk' => $params['disk'] ?? null,
            'name' => $params['name'] ?? null,
            'expires' => $expires,
        ];
    }

    /**
     * @param array<string, mixed> $parts
     */
    private function canonicalBase(array $parts): string
    {
        $target = '';
        if (isset($parts['scheme'], $parts['host']) && is_string($parts['scheme']) && is_string($parts['host'])) {
            $target = strtolower($parts['scheme']) . '://' . strtolower($parts['host'])
                . (isset($parts['port']) && is_int($parts['port']) ? ':' . $parts['port'] : '');
        }
        $path = isset($parts['path']) && is_string($parts['path']) && '' !== $parts['path'] ? $parts['path'] : '/';

        return $target . $path;
    }

    private function validateKey(string $key): string
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

    /** @param array<string, string> $params */
    private function encodeQuery(array $params): string
    {
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = rawurlencode($k) . '=' . rawurlencode($v);
        }

        return implode('&', $pairs);
    }

    /** @return array<string, string>|null */
    private static function parseStrictQuery(string $query): ?array
    {
        if ('' === $query) {
            return null;
        }
        $pairs = explode('&', $query);
        if (count($pairs) > self::MAX_QUERY_PARAMS) {
            return null;
        }

        $params = [];
        foreach ($pairs as $pair) {
            if ('' === $pair) {
                return null;
            }
            $eqPos = strpos($pair, '=');
            if (false === $eqPos || 0 === $eqPos) {
                return null;
            }
            $rawKey = substr($pair, 0, $eqPos);
            $rawVal = substr($pair, $eqPos + 1);
            $key = rawurldecode($rawKey);
            $val = rawurldecode($rawVal);

            if (
                '' === trim($key)
                || str_contains($key, '[')
                || str_contains($key, ']')
                || array_key_exists($key, $params)
                || rawurlencode($key) !== $rawKey
                || rawurlencode($val) !== $rawVal
            ) {
                return null;
            }
            $params[$key] = $val;
        }

        return $params;
    }
}
