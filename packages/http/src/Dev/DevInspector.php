<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

use Kongono\Core\Diagnostics\SecretScrubber;

/**
 * Persistent-runtime-safe bounded ring-buffer inspector capturing recent HTTP requests,
 * latency, status codes, SQL query telemetry, and scrubbed error messages for Kongono Dev Studio.
 */
final class DevInspector
{
    /**
     * @var list<array{
     *     id: int,
     *     timestamp: string,
     *     method: string,
     *     path: string,
     *     status: int,
     *     duration_ms: float,
     *     queries: list<array{sql: string, ms: float}>,
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

    public function recordRequest(string $method, string $path, int $status, float $durationMs, ?string $error = null): void
    {
        $this->traces[] = [
            'id' => ++$this->sequence,
            'timestamp' => gmdate(DATE_ATOM),
            'method' => strtoupper($method),
            'path' => SecretScrubber::scrub($path),
            'status' => $status,
            'duration_ms' => round($durationMs, 2),
            'queries' => $this->currentQueries,
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
     *     status: int,
     *     duration_ms: float,
     *     queries: list<array{sql: string, ms: float}>,
     *     error: ?string
     * }>
     */
    public function traces(): array
    {
        return array_reverse($this->traces);
    }

    public function clear(): void
    {
        $this->traces = [];
        $this->currentQueries = [];
    }
}
