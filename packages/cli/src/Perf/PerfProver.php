<?php

declare(strict_types=1);

namespace Kongono\Cli\Perf;

use Closure;
use Kongono\Cli\Diagnostics\AppFinding;
use Kongono\Cli\Shadow\ShadowDiffProver;
use Kongono\Http\App;
use Kongono\Http\Request;

/**
 * Built-in Persistent-Worker Leak & SQL N+1 Prover (`kongo perf` & MCP `kongono_perf_probe`).
 *
 * Boots a Kongono `App` in-process, fires each safe GET route repeatedly inside the same PHP worker
 * lifecycle (simulating FrankenPHP / RoadRunner), and empirically proves:
 *  1. Zero leaked operation scopes (`Kernel::activeOperationCount() === 0`)
 *  2. Zero superglobal (`$_GET`/`$_POST`/`$_COOKIE`) cross-request pollution
 *  3. Bounded steady-state memory growth after warmup
 *  4. Zero SQL N+1 query loops (repeated normalized SQL shapes within a single request)
 *  5. Route latency within budget (P50 / P95 / Max ms) & zero 5xx errors
 */
final readonly class PerfProver
{
    /**
     * @param list<string>|null                            $paths               Optional explicit GET paths (defaults to all safe static GET routes)
     * @param (Closure(Closure(string, float): void): Closure)|null $attachQueryListener Optional custom SQL query listener binder (defaults to `PdoConnection::listenQuery`)
     * @return array{
     *     score: int,
     *     passed: bool,
     *     iterations: int,
     *     max_ms_budget: float,
     *     routes_probed: int,
     *     total_queries: int,
     *     n_plus_one_detected: int,
     *     leaks_detected: int,
     *     routes: list<array{
     *         path: string,
     *         status: int,
     *         p50_ms: float,
     *         p95_ms: float,
     *         max_ms: float,
     *         queries_per_req: int,
     *         sql_ms_per_req: float,
     *         memory_delta_bytes: int,
     *         bytes_per_req: int,
     *         active_ops_after: int,
     *         n_plus_one: list<array{sql_shape: string, count: int}>
     *     }>,
     *     findings: list<array{
     *         code: string,
     *         severity: 'error'|'warning',
     *         who: string,
     *         what: string,
     *         why: string,
     *         where: string,
     *         file: ?string,
     *         line: ?int,
     *         fixable: bool,
     *         how_to_fix: list<string>,
     *         code_fix: ?string,
     *         command: ?string,
     *         did_you_mean: list<string>
     *     }>
     * }
     */
    public function prove(
        App $app,
        ?array $paths = null,
        int $iterations = 3,
        float $maxMs = 250.0,
        int $nPlusOneThreshold = 3,
        int $maxLeakBytesPerReq = 262_144,
        ?Closure $attachQueryListener = null,
    ): array {
        $iters = max(1, min(50, $iterations));
        $rawTargets = $paths ?? ShadowDiffProver::discoverSafeGetPathsForApp($app);
        $targetPaths = array_values(array_filter($rawTargets, ShadowDiffProver::isSafeProbePath(...)));

        /** @var list<array{sql: string, ms: float}> $recordedQueries */
        $recordedQueries = [];
        $unsubscribe = ShadowDiffProver::bindQueryListener($app, $attachQueryListener, static function (string $sql, float $ms) use (&$recordedQueries): void {
            $recordedQueries[] = ['sql' => $sql, 'ms' => $ms];
        });

        /** @var list<AppFinding> $findings */
        $findings = [];
        $routeSummaries = [];
        $totalQueries = 0;
        $nPlusOneCount = 0;
        $leaksDetected = 0;

        try {
            foreach ($targetPaths as $path) {
                $recordedQueries = [];
                $getBefore = $_GET;
                $postBefore = $_POST;
                $cookieBefore = $_COOKIE;

                // Warmup request (compiles views, fills router/DI caches — realistic steady state for FrankenPHP/RoadRunner)
                $app->handle(Request::create('GET', $path));
                gc_collect_cycles();

                $memBefore = memory_get_usage(false);
                $baselineActiveOps = $app->kernel()->activeOperationCount();

                /** @var list<float> $latencies */
                $latencies = [];
                $lastStatus = 200;
                $maxActiveOps = 0;
                $lastQueries = [];

                for ($i = 0; $i < $iters; ++$i) {
                    $recordedQueries = [];
                    $t0 = hrtime(true);
                    $resp = $app->handle(Request::create('GET', $path));
                    $latencies[] = (hrtime(true) - $t0) / 1_000_000;
                    $lastStatus = $resp->getStatusCode();
                    $activeOps = max(0, $app->kernel()->activeOperationCount() - $baselineActiveOps);
                    if ($activeOps > $maxActiveOps) {
                        $maxActiveOps = $activeOps;
                    }
                    $lastQueries = $recordedQueries;
                }

                gc_collect_cycles();
                $memDelta = max(0, memory_get_usage(false) - $memBefore);
                $bytesPerReq = (int) round($memDelta / $iters);

                $globalsMutated = $_GET !== $getBefore || $_POST !== $postBefore || $_COOKIE !== $cookieBefore;
                if ($globalsMutated) {
                    $_GET = $getBefore;
                    $_POST = $postBefore;
                    $_COOKIE = $cookieBefore;
                }

                sort($latencies);
                $p50 = round($latencies[(int) floor((count($latencies) - 1) * 0.5)], 2);
                $p95 = round($latencies[(int) floor((count($latencies) - 1) * 0.95)], 2);
                $maxLat = round($latencies[count($latencies) - 1], 2);

                $qCount = count($lastQueries);
                $totalQueries += $qCount;
                $sqlMs = round(array_sum(array_column($lastQueries, 'ms')), 2);
                $nPlusOnes = $this->detectNPlusOne($lastQueries, $nPlusOneThreshold);

                $routeSummaries[] = [
                    'path' => $path,
                    'status' => $lastStatus,
                    'p50_ms' => $p50,
                    'p95_ms' => $p95,
                    'max_ms' => $maxLat,
                    'queries_per_req' => $qCount,
                    'sql_ms_per_req' => $sqlMs,
                    'memory_delta_bytes' => $memDelta,
                    'bytes_per_req' => $bytesPerReq,
                    'active_ops_after' => $maxActiveOps,
                    'n_plus_one' => $nPlusOnes,
                ];

                if ($lastStatus >= 500) {
                    $findings[] = new AppFinding(
                        code: 'KGN-PERF-ROUTE-5XX',
                        severity: 'error',
                        who: 'Kongono Persistent-Worker Prover',
                        what: sprintf('`GET %s` returned HTTP %d during persistent-worker probing.', $path, $lastStatus),
                        why: 'Route failed with a server error while executing under multi-request worker reuse.',
                        where: 'GET ' . $path,
                        howToFix: [
                            sprintf('Run `kongo incident:list` or visit `%s` with `APP_DEBUG=true` for full 5W1H diagnostics.', $path),
                        ],
                    );
                }

                if ($maxActiveOps > 0) {
                    ++$leaksDetected;
                    $findings[] = new AppFinding(
                        code: 'KGN-PERF-ACTIVE-OPS-LEAK',
                        severity: 'error',
                        who: 'Kongono Kernel Lifecycle Prover',
                        what: sprintf('`GET %s` left %d active operation scope(s) unclosed after returning.', $path, $maxActiveOps),
                        why: 'Unclosed operation scopes leak resources across requests in FrankenPHP / RoadRunner workers.',
                        where: 'GET ' . $path,
                        howToFix: [
                            'Wrap manual `beginOperation()` calls in `runOperation(...)` or close them inside a `finally` block.',
                        ],
                    );
                }

                if ($globalsMutated) {
                    ++$leaksDetected;
                    $findings[] = new AppFinding(
                        code: 'KGN-PERF-GLOBAL-MUTATION',
                        severity: 'error',
                        who: 'Kongono Persistent-Worker Guard',
                        what: sprintf('`GET %s` mutated PHP superglobals (`$_GET`, `$_POST`, or `$_COOKIE`).', $path),
                        why: 'Mutating PHP superglobals leaks one request\'s input into subsequent requests in persistent runtimes.',
                        where: 'GET ' . $path,
                        howToFix: [
                            'Read request data exclusively from the injected `Kongono\\Http\\Request` object instead of mutating superglobals.',
                        ],
                    );
                }

                if ($bytesPerReq > $maxLeakBytesPerReq) {
                    ++$leaksDetected;
                    $findings[] = new AppFinding(
                        code: 'KGN-PERF-MEMORY-LEAK',
                        severity: 'warning',
                        who: 'Kongono Persistent-Worker Memory Prover',
                        what: sprintf('`GET %s` retained ~%d KB/request across %d consecutive worker requests (budget: %d KB/req).', $path, (int) round($bytesPerReq / 1024), $iters, (int) round($maxLeakBytesPerReq / 1024)),
                        why: 'Unbounded per-request memory growth causes FrankenPHP / RoadRunner workers to OOM under sustained traffic.',
                        where: 'GET ' . $path,
                        howToFix: [
                            'Check singleton services and caches invoked by this route for unbounded in-memory arrays; bind request-scoped state via `$app->container()->scoped(...)`.',
                        ],
                    );
                }

                foreach ($nPlusOnes as $npo) {
                    ++$nPlusOneCount;
                    $findings[] = new AppFinding(
                        code: 'KGN-PERF-N-PLUS-ONE',
                        severity: 'warning',
                        who: 'Kongono SQL N+1 Prover',
                        what: sprintf('`GET %s` executed the same SQL shape %dx in one request: `%s`.', $path, $npo['count'], $npo['sql_shape']),
                        why: 'Executing repeated identical queries inside a loop scales linearly with record count and degrades latency under load.',
                        where: 'GET ' . $path,
                        howToFix: [
                            'Batch the lookup using `Kongono\\Database\\BatchLoader`, a SQL `JOIN`, or `->whereIn(\'id\', $ids)`.',
                        ],
                        codeFix: '$itemsByParent = BatchLoader::hasMany($db, $parents, \'items\', \'parent_id\');',
                    );
                }

                if ($p95 > $maxMs) {
                    $findings[] = new AppFinding(
                        code: 'KGN-PERF-SLOW-ROUTE',
                        severity: 'warning',
                        who: 'Kongono Latency Budget Prover',
                        what: sprintf('`GET %s` P95 latency was %.2fms (exceeds budget of %.0fms).', $path, $p95, $maxMs),
                        why: 'Slow synchronous responses reduce worker concurrency and user responsiveness.',
                        where: 'GET ' . $path,
                        howToFix: [
                            'Profile slow queries or offload heavy work to `Kongono\\Jobs\\Queue`; run `kongo optimize` to precompile views.',
                        ],
                    );
                }
            }
        } finally {
            if (null !== $unsubscribe) {
                $unsubscribe();
            }
        }

        $errors = 0;
        $warnings = 0;
        foreach ($findings as $f) {
            if ('error' === $f->severity) {
                ++$errors;
            } else {
                ++$warnings;
            }
        }

        return [
            'score' => max(0, 100 - ($errors * 20) - ($warnings * 10)),
            'passed' => 0 === $errors && 0 === $leaksDetected,
            'iterations' => $iters,
            'max_ms_budget' => $maxMs,
            'routes_probed' => count($routeSummaries),
            'total_queries' => $totalQueries,
            'n_plus_one_detected' => $nPlusOneCount,
            'leaks_detected' => $leaksDetected,
            'routes' => $routeSummaries,
            'findings' => array_map(static fn(AppFinding $f): array => $f->toArray(), $findings),
        ];
    }

    /**
     * @param list<array{sql: string, ms: float}> $queries
     * @return list<array{sql_shape: string, count: int}>
     */
    private function detectNPlusOne(array $queries, int $threshold): array
    {
        /** @var array<string, int> $counts */
        $counts = [];
        foreach ($queries as $q) {
            $shape = $this->normalizeSqlShape($q['sql']);
            if ('' === $shape) {
                continue;
            }
            $counts[$shape] = ($counts[$shape] ?? 0) + 1;
        }

        $flagged = [];
        foreach ($counts as $shape => $count) {
            if ($count >= $threshold) {
                $flagged[] = ['sql_shape' => $shape, 'count' => $count];
            }
        }

        return $flagged;
    }

    private function normalizeSqlShape(string $sql): string
    {
        $compact = trim((string) preg_replace('/\s+/', ' ', $sql));
        if ('' === $compact) {
            return '';
        }
        // Replace quoted literals & numbers with `?` so `WHERE id = 1` and `WHERE id = 2` collapse to one shape
        $normalized = (string) preg_replace(["/'[^']*'/", '/\b\d+\b/'], '?', $compact);

        return substr($normalized, 0, 160);
    }
}
