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
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * }>
     */
    private array $traces = [];

    /** @var list<array{sql: string, ms: float}> */
    private array $currentQueries = [];

    private int $sequence = 0;

    /** @param positive-int $maxTraces */
    public function __construct(
        private readonly int $maxTraces = 50,
        private readonly int $nPlusOneThreshold = 5,
    ) {}

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
    ): void {
        $safeHeaders = [];
        foreach ($headers as $name => $val) {
            $joined = is_array($val) ? implode(', ', $val) : $val;
            $isSensitive = in_array(strtolower($name), self::REDACTED_HEADERS, true) || SecretScrubber::isSensitiveKey($name);
            $safeHeaders[$name] = $isSensitive
                ? '[REDACTED]'
                : SecretScrubber::scrub($joined);
        }

        $nPlusOne = $this->detectNPlusOne($this->currentQueries);

        $scrubbedBody = $this->scrubBody($body);

        $this->traces[] = [
            'id' => ++$this->sequence,
            'timestamp' => gmdate(DATE_ATOM),
            'method' => strtoupper($method),
            'path' => SecretScrubber::scrub($path),
            'query' => SecretScrubber::scrub($query),
            'headers' => $safeHeaders,
            'body' => substr($scrubbedBody, 0, 8192),
            'status' => $status,
            'duration_ms' => round($durationMs, 2),
            'queries' => $this->currentQueries,
            'n_plus_one' => $nPlusOne,
            'error' => null !== $error ? SecretScrubber::scrub($error) : null,
        ];
        $this->currentQueries = [];

        if (count($this->traces) > $this->maxTraces) {
            array_shift($this->traces);
        }
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
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * }>
     */
    public function traces(): array
    {
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
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * }|null
     */
    public function findTrace(int $id): ?array
    {
        foreach ($this->traces as $trace) {
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

    private function scrubBody(string $body): string
    {
        if ('' === $body) {
            return '';
        }

        $trimmed = ltrim($body);
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $scrubbed = SecretScrubber::scrubArray($decoded);
                $reencoded = json_encode($scrubbed, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
                if (is_string($reencoded)) {
                    return $reencoded;
                }
            }
        } elseif (1 === preg_match('/Content-Disposition:\s*form-data;/i', $body)) {
            return '[MULTIPART BODY REDACTED]';
        } elseif (!str_starts_with($trimmed, '<') && str_contains($body, '=') && !str_contains($body, "\n") && !str_contains($body, "\r")) {
            parse_str($body, $formFields);
            if ([] !== $formFields) {
                return http_build_query(SecretScrubber::scrubArray($formFields), '', '&', PHP_QUERY_RFC3986);
            }
        }

        return SecretScrubber::scrub($body);
    }
}
