<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

use Kongono\Core\Diagnostics\SecretScrubber;

/**
 * Persistent-runtime-safe bounded ring-buffer inspector capturing recent HTTP requests,
 * scrubbed replay snapshots (headers/query/body), SQL query telemetry, N+1 alerts, and errors
 * for Kongono Dev Studio (`/_kongono`).
 */
final class DevInspector
{
    /** @var list<string> */
    private const array REDACTED_HEADERS = [
        'authorization',
        'cookie',
        'set-cookie',
        'x-api-key',
        'x-csrf-token',
        'proxy-authorization',
    ];

    /**
     * @var list<array{
     *     id: int,
     *     timestamp: string,
     *     method: string,
     *     path: string,
     *     query: string,
     *     headers: array<string, string>,
     *     body: string,
     *     status: int,
     *     duration_ms: float,
     *     memory_delta_kb: float,
     *     response_headers: array<string, string>,
     *     response_body: string,
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * }>
     */
    private array $traces = [];

    /** @var list<array{sql: string, ms: float}> */
    private array $currentQueries = [];

    private int $sequence = 0;

    private ?string $storageFile = null;

    /** @param positive-int $maxTraces */
    public function __construct(
        private readonly int $maxTraces = 50,
        private readonly int $nPlusOneThreshold = 5,
        ?string $storageFile = null,
    ) {
        if (null !== $storageFile && '' !== trim($storageFile)) {
            $this->storageFile = $storageFile;
        }
    }

    /**
     * Attach a bounded JSON ring-buffer file (e.g. `storage/dev/traces.json`) so captured traces
     * persist across stateless requests (`kongo serve`, PHP-FPM) and multi-worker pools.
     */
    public function useStorageFile(string $filePath): void
    {
        if (null === $this->storageFile && '' !== trim($filePath)) {
            $this->storageFile = $filePath;
        }
    }

    public function storageFile(): ?string
    {
        return $this->storageFile;
    }

    public function beginRequest(): void
    {
        $this->currentQueries = [];
    }

    /** Plugs directly into `PdoConnection::onQuery($inspector->recordQuery(...))`. */
    public function recordQuery(string $sql, float $durationMs): void
    {
        if (count($this->currentQueries) >= 100) {
            return;
        }
        $this->currentQueries[] = [
            'sql' => SecretScrubber::scrub($sql),
            'ms' => round($durationMs, 3),
        ];
    }

    /**
     * @param array<string, string|array<array-key, string>> $headers
     * @param array<string, string|array<array-key, string>> $responseHeaders
     */
    public function recordRequest(
        string $method,
        string $path,
        int $status,
        float $durationMs,
        ?string $error = null,
        string $query = '',
        array $headers = [],
        string $body = '',
        float $memoryDeltaKb = 0.0,
        array $responseHeaders = [],
        string $responseBody = '',
    ): int {
        [$safeHeaders, $contentType] = $this->scrubHeaders($headers);
        [$safeResponseHeaders, $responseContentType] = $this->scrubHeaders($responseHeaders);

        $nPlusOne = $this->detectNPlusOne($this->currentQueries);

        $scrubbedBody = $this->scrubBody($body, $contentType);
        $scrubbedResponseBody = $this->scrubBody($responseBody, $responseContentType);

        $trace = [
            'id' => ++$this->sequence,
            'timestamp' => gmdate(DATE_ATOM),
            'method' => strtoupper($method),
            'path' => SecretScrubber::scrub($path),
            'query' => SecretScrubber::scrub($query),
            'headers' => $safeHeaders,
            'body' => substr($scrubbedBody, 0, 8192),
            'status' => $status,
            'duration_ms' => round($durationMs, 2),
            'memory_delta_kb' => round($memoryDeltaKb, 1),
            'response_headers' => $safeResponseHeaders,
            'response_body' => substr($scrubbedResponseBody, 0, 4096),
            'queries' => $this->currentQueries,
            'n_plus_one' => $nPlusOne,
            'error' => null !== $error ? SecretScrubber::scrub($error) : null,
        ];
        $this->currentQueries = [];

        if (null !== $this->storageFile) {
            return $this->appendToStorageFile($this->storageFile, $trace);
        }

        $this->traces[] = $trace;

        if (count($this->traces) > $this->maxTraces) {
            array_shift($this->traces);
        }

        return $trace['id'];
    }

    /**
     * Return captured traces ordered newest-first.
     *
     * @return list<array{
     *     id: int,
     *     timestamp: string,
     *     method: string,
     *     path: string,
     *     query: string,
     *     headers: array<string, string>,
     *     body: string,
     *     status: int,
     *     duration_ms: float,
     *     memory_delta_kb: float,
     *     response_headers: array<string, string>,
     *     response_body: string,
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * }>
     */
    public function traces(): array
    {
        if (null !== $this->storageFile) {
            [, $stored] = $this->readStorageFile($this->storageFile);

            return array_reverse($stored);
        }

        return array_reverse($this->traces);
    }

    /**
     * Find a specific captured trace by ID for time-travel replay or PHPUnit test export.
     *
     * @return array{
     *     id: int,
     *     timestamp: string,
     *     method: string,
     *     path: string,
     *     query: string,
     *     headers: array<string, string>,
     *     body: string,
     *     status: int,
     *     duration_ms: float,
     *     memory_delta_kb: float,
     *     response_headers: array<string, string>,
     *     response_body: string,
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * }|null
     */
    public function findTrace(int $id): ?array
    {
        foreach ($this->traces() as $trace) {
            if ($trace['id'] === $id) {
                return $trace;
            }
        }

        return null;
    }

    public function clear(): void
    {
        $this->traces = [];
        $this->currentQueries = [];
        if (null !== $this->storageFile && !$this->isSymlink($this->storageFile) && is_file($this->storageFile)) {
            @unlink($this->storageFile);
        }
    }

    /**
     * @param list<array{sql: string, ms: float}> $queries
     * @return list<array{pattern: string, count: int}>
     */
    private function detectNPlusOne(array $queries): array
    {
        $counts = [];
        foreach ($queries as $q) {
            $pattern = trim((string) preg_replace('/\s+/', ' ', $q['sql']));
            $pattern = (string) preg_replace("/'(?:''|[^'])*'/", '?', $pattern);
            $pattern = (string) preg_replace('/\b\d+(?:\.\d+)?\b/', '?', $pattern);
            $counts[$pattern] = ($counts[$pattern] ?? 0) + 1;
        }

        $warnings = [];
        foreach ($counts as $pattern => $count) {
            if ($count >= $this->nPlusOneThreshold) {
                $warnings[] = ['pattern' => $pattern, 'count' => $count];
            }
        }

        return $warnings;
    }

    /**
     * @param array<string, string|array<array-key, string>> $headers
     * @return array{0: array<string, string>, 1: string}
     */
    private function scrubHeaders(array $headers): array
    {
        $safeHeaders = [];
        $contentType = '';
        foreach ($headers as $name => $val) {
            $joined = is_array($val) ? implode(', ', $val) : $val;
            $lowerName = strtolower($name);
            if ('content-type' === $lowerName) {
                $contentType = $joined;
            }
            $isSensitive = in_array($lowerName, self::REDACTED_HEADERS, true) || SecretScrubber::isSensitiveKey($name);
            $safeHeaders[$name] = $isSensitive
                ? '[REDACTED]'
                : SecretScrubber::scrub($joined);
        }

        return [$safeHeaders, $contentType];
    }

    private function scrubBody(string $body, string $contentType = ''): string
    {
        if ('' === $body) {
            return '';
        }

        if (str_contains(strtolower($contentType), 'multipart/') || 1 === preg_match('/Content-Disposition:\s*form-data;/i', $body)) {
            return '[MULTIPART BODY REDACTED]';
        }

        $trimmed = ltrim($body);
        if (str_starts_with($trimmed, '<')) {
            $xmlSafe = preg_replace(
                '/(<(?P<tag>(?:[a-zA-Z0-9_-]+:)?(?:password|passwd|pwd|secret|token|api[_-]?key|access[_-]?token|refresh[_-]?token|client[_-]?secret|authorization|private[_-]?key|ssn|credit[_-]?card|card[_-]?number|cvv))\b[^>]*>)(.*?)(<\/(?P=tag)\s*>)/is',
                '$1[REDACTED]$4',
                $body,
            );
            if (is_string($xmlSafe)) {
                $body = $xmlSafe;
            }
            $xmlAttrSafe = preg_replace(
                '/(\b(?:password|passwd|secret|token|api[_-]?key|access[_-]?token|client[_-]?secret|authorization)\s*=\s*)(["\'])[^"\']*\2/i',
                '$1$2[REDACTED]$2',
                $body,
            );
            if (is_string($xmlAttrSafe)) {
                $body = $xmlAttrSafe;
            }
        } elseif (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $scrubbed = SecretScrubber::scrubArray($decoded);
                $reencoded = json_encode($scrubbed, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
                if (is_string($reencoded)) {
                    return $reencoded;
                }
            }
        } elseif (str_contains($body, '=') && !str_contains($body, "\n") && !str_contains($body, "\r")) {
            parse_str($body, $formFields);
            if ([] !== $formFields) {
                return http_build_query(SecretScrubber::scrubArray($formFields), '', '&', PHP_QUERY_RFC3986);
            }
        }

        return SecretScrubber::scrub($body);
    }

    /**
     * @param array{
     *     id: int,
     *     timestamp: string,
     *     method: string,
     *     path: string,
     *     query: string,
     *     headers: array<string, string>,
     *     body: string,
     *     status: int,
     *     duration_ms: float,
     *     memory_delta_kb: float,
     *     response_headers: array<string, string>,
     *     response_body: string,
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * } $trace
     */
    private function appendToStorageFile(string $filePath, array $trace): int
    {
        $dir = dirname($filePath);
        if ($this->isSymlink($filePath) || $this->isSymlink($dir)) {
            return $trace['id'];
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0o700, true);
            if (!is_dir($dir)) {
                return $trace['id'];
            }
        }

        $handle = @fopen($filePath, 'c+b');
        if (false === $handle) {
            return $trace['id'];
        }

        try {
            @chmod($filePath, 0o600);
            if (!flock($handle, LOCK_EX)) {
                return $trace['id'];
            }
            $raw = stream_get_contents($handle);
            [$seq, $existing] = is_string($raw) ? $this->decodeStorage($raw) : [0, []];
            $nextId = max($seq, $this->sequence - 1) + 1;
            $this->sequence = $nextId;
            $trace['id'] = $nextId;
            $existing[] = $trace;
            if (count($existing) > $this->maxTraces) {
                $existing = array_slice($existing, -$this->maxTraces);
            }
            $this->traces = $existing;
            $encoded = json_encode(
                ['sequence' => $nextId, 'traces' => $existing],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE,
            );
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $encoded);
            fflush($handle);

            return $nextId;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return array{0: int, 1: list<array{
     *     id: int,
     *     timestamp: string,
     *     method: string,
     *     path: string,
     *     query: string,
     *     headers: array<string, string>,
     *     body: string,
     *     status: int,
     *     duration_ms: float,
     *     memory_delta_kb: float,
     *     response_headers: array<string, string>,
     *     response_body: string,
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * }>}
     */
    private function readStorageFile(string $filePath): array
    {
        if ($this->isSymlink($filePath) || !is_file($filePath)) {
            return [0, []];
        }
        $handle = @fopen($filePath, 'rb');
        if (false === $handle) {
            return [0, []];
        }
        try {
            flock($handle, LOCK_SH);
            $raw = stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return is_string($raw) ? $this->decodeStorage($raw) : [0, []];
    }

    /**
     * @return array{0: int, 1: list<array{
     *     id: int,
     *     timestamp: string,
     *     method: string,
     *     path: string,
     *     query: string,
     *     headers: array<string, string>,
     *     body: string,
     *     status: int,
     *     duration_ms: float,
     *     memory_delta_kb: float,
     *     response_headers: array<string, string>,
     *     response_body: string,
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * }>}
     */
    private function decodeStorage(string $raw): array
    {
        if ('' === trim($raw)) {
            return [0, []];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [0, []];
        }
        $seq = is_int($decoded['sequence'] ?? null) ? $decoded['sequence'] : 0;
        $rawTraces = is_array($decoded['traces'] ?? null) ? $decoded['traces'] : [];
        $out = [];
        foreach ($rawTraces as $item) {
            if (!is_array($item) || !is_int($item['id'] ?? null) || !is_string($item['path'] ?? null)) {
                continue;
            }
            $headers = [];
            if (is_array($item['headers'] ?? null)) {
                foreach ($item['headers'] as $k => $v) {
                    if (is_string($k) && is_string($v)) {
                        $headers[$k] = $v;
                    }
                }
            }
            $responseHeaders = [];
            if (is_array($item['response_headers'] ?? null)) {
                foreach ($item['response_headers'] as $k => $v) {
                    if (is_string($k) && is_string($v)) {
                        $responseHeaders[$k] = $v;
                    }
                }
            }
            $queries = [];
            if (is_array($item['queries'] ?? null)) {
                foreach ($item['queries'] as $q) {
                    if (is_array($q) && is_string($q['sql'] ?? null) && (is_float($q['ms'] ?? null) || is_int($q['ms'] ?? null))) {
                        $queries[] = ['sql' => $q['sql'], 'ms' => (float) $q['ms']];
                    }
                }
            }
            $nPlusOne = [];
            if (is_array($item['n_plus_one'] ?? null)) {
                foreach ($item['n_plus_one'] as $alert) {
                    if (is_array($alert) && is_string($alert['pattern'] ?? null) && is_int($alert['count'] ?? null)) {
                        $nPlusOne[] = ['pattern' => $alert['pattern'], 'count' => $alert['count']];
                    }
                }
            }
            $duration = $item['duration_ms'] ?? 0.0;
            $mem = $item['memory_delta_kb'] ?? 0.0;
            $out[] = [
                'id' => $item['id'],
                'timestamp' => is_string($item['timestamp'] ?? null) ? $item['timestamp'] : '',
                'method' => is_string($item['method'] ?? null) ? $item['method'] : 'GET',
                'path' => $item['path'],
                'query' => is_string($item['query'] ?? null) ? $item['query'] : '',
                'headers' => $headers,
                'body' => is_string($item['body'] ?? null) ? $item['body'] : '',
                'status' => is_int($item['status'] ?? null) ? $item['status'] : 200,
                'duration_ms' => (is_float($duration) || is_int($duration)) ? (float) $duration : 0.0,
                'memory_delta_kb' => (is_float($mem) || is_int($mem)) ? (float) $mem : 0.0,
                'response_headers' => $responseHeaders,
                'response_body' => is_string($item['response_body'] ?? null) ? $item['response_body'] : '',
                'queries' => $queries,
                'n_plus_one' => $nPlusOne,
                'error' => is_string($item['error'] ?? null) ? $item['error'] : null,
            ];
            if ($item['id'] > $seq) {
                $seq = $item['id'];
            }
        }

        return [$seq, $out];
    }

    private function isSymlink(string $path): bool
    {
        clearstatcache(true, $path);

        return is_link($path);
    }
}
