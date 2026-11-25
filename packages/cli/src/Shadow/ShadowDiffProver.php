<?php

declare(strict_types=1);

namespace Kongono\Cli\Shadow;

use Closure;
use Kongono\Cli\Diagnostics\AppFinding;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\PdoConnection;
use Kongono\Http\App;
use Kongono\Http\Request;
use Throwable;

/**
 * Deterministic Behavioral Equivalence Prover (`kongo shadow:record` / `kongo shadow:diff` & MCP `kongono_shadow_diff`).
 *
 * Captures a golden behavioral fingerprint (status, content-type, recursive JSON key/type shape,
 * HTML landmark/form outline, and SQL query count) across routes, then replays against a refactored
 * implementation to prove zero unintended behavioral or query regressions.
 */
final readonly class ShadowDiffProver
{
    private const int MAX_REPLAY_ROUTES = 100;
    /**
     * Record a golden behavioral corpus across safe GET routes (or explicit `$paths`).
     *
     * @param list<string>|null                                    $paths
     * @param (Closure(Closure(string, float): void): Closure)|null $attachQueryListener
     * @return array{
     *     app_name: string,
     *     recorded_at: string,
     *     entries: array<string, array{
     *         path: string,
     *         status: int,
     *         content_type: string,
     *         json_shape: ?array<string, mixed>,
     *         html_outline: ?array{title: string, headings: list<string>, forms: int, links: int},
     *         query_count: int,
     *         elapsed_ms: float
     *     }>
     * }
     */
    public function record(App $app, ?array $paths = null, ?Closure $attachQueryListener = null): array
    {
        $rawTargets = $paths ?? self::discoverSafeGetPathsForApp($app);
        $targetPaths = array_values(array_filter($rawTargets, self::isSafeProbePath(...)));
        $entries = [];

        $queryCount = 0;
        $unsubscribe = self::bindQueryListener($app, $attachQueryListener, static function (string $_sql, float $_ms) use (&$queryCount): void {
            ++$queryCount;
        });

        try {
            foreach ($targetPaths as $path) {
                $queryCount = 0;
                $t0 = hrtime(true);
                $resp = $app->handle(Request::create('GET', $path));
                $elapsedMs = round((hrtime(true) - $t0) / 1_000_000, 2);

                $rawContentType = $resp->getHeaderLine('Content-Type');
                $mediaType = strtolower(trim(explode(';', $rawContentType, 2)[0]));
                $body = (string) $resp->getBody();

                $jsonShape = null;
                $htmlOutline = null;
                if (str_contains($mediaType, 'json')) {
                    $decoded = json_decode($body, true);
                    if (is_array($decoded)) {
                        $jsonShape = $this->extractJsonShape($decoded);
                    }
                } elseif (str_contains($mediaType, 'html')) {
                    $htmlOutline = $this->extractHtmlOutline($body);
                }

                $entries[$path] = [
                    'path' => $path,
                    'status' => $resp->getStatusCode(),
                    'content_type' => $mediaType,
                    'json_shape' => $jsonShape,
                    'html_outline' => $htmlOutline,
                    'query_count' => $queryCount,
                    'elapsed_ms' => $elapsedMs,
                ];
            }
        } finally {
            if (null !== $unsubscribe) {
                $unsubscribe();
            }
        }

        return [
            'app_name' => $app->graph()->appName,
            'recorded_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'entries' => $entries,
        ];
    }

    /**
     * Replay a previously recorded golden corpus against `$app` and prove behavioral equivalence.
     *
     * @param array<string, mixed>                                 $baseline
     * @param (Closure(Closure(string, float): void): Closure)|null $attachQueryListener
     * @return array{
     *     equivalent: bool,
     *     routes_compared: int,
     *     regressions: int,
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
    public function diff(array $baseline, App $app, ?Closure $attachQueryListener = null): array
    {
        $rawEntries = is_array($baseline['entries'] ?? null) ? $baseline['entries'] : [];
        /** @var list<string> $paths */
        $paths = [];
        /** @var list<AppFinding> $findings */
        $findings = [];
        $regressions = 0;

        foreach (array_keys($rawEntries) as $k) {
            if (!is_string($k)) {
                continue;
            }
            if (!self::isSafeProbePath($k)) {
                ++$regressions;
                $findings[] = new AppFinding(
                    code: 'KGN-SHADOW-UNSAFE-PATH',
                    severity: 'error',
                    who: 'Kongono Behavioral Equivalence Prover',
                    what: sprintf('Baseline path `%s` is blocked from automated shadow replay.', $k),
                    why: 'Shadow replay refuses internal Dev Studio, Ops, MCP, logout, and traversal paths to prevent unintended side effects.',
                    where: 'GET ' . $k,
                    howToFix: [
                        'Remove internal/unsafe paths from the shadow baseline or re-record with `kongo shadow:record`.',
                    ],
                );
                continue;
            }
            if (count($paths) >= self::MAX_REPLAY_ROUTES) {
                ++$regressions;
                $findings[] = new AppFinding(
                    code: 'KGN-SHADOW-BASELINE-LIMIT',
                    severity: 'error',
                    who: 'Kongono Behavioral Equivalence Prover',
                    what: sprintf('Shadow baseline exceeds maximum replay bound of %d routes.', self::MAX_REPLAY_ROUTES),
                    why: 'Replay is capped to prevent unbounded in-process request amplification from untrusted baselines.',
                    where: 'shadow baseline',
                    howToFix: [
                        sprintf('Limit shadow baselines to at most %d safe GET routes.', self::MAX_REPLAY_ROUTES),
                    ],
                );
                break;
            }
            $paths[] = $k;
        }

        $current = $this->record($app, $paths, $attachQueryListener);

        foreach ($paths as $path) {
            $old = $rawEntries[$path] ?? null;
            $new = $current['entries'][$path] ?? null;
            if (!is_array($old) || null === $new) {
                continue;
            }

            $oldStatus = is_int($old['status'] ?? null) ? $old['status'] : 200;
            if ($oldStatus !== $new['status']) {
                ++$regressions;
                $findings[] = new AppFinding(
                    code: 'KGN-SHADOW-STATUS-CHANGED',
                    severity: 'error',
                    who: 'Kongono Behavioral Equivalence Prover',
                    what: sprintf('`GET %s` changed HTTP status from %d to %d.', $path, $oldStatus, $new['status']),
                    why: 'Refactored route no longer returns the same HTTP status code as the recorded baseline.',
                    where: 'GET ' . $path,
                    howToFix: [
                        sprintf('Inspect why `GET %s` now returns HTTP %d instead of %d, or re-record baseline if intentional (`kongo shadow:record`).', $path, $new['status'], $oldStatus),
                    ],
                );
            }

            $oldCt = is_string($old['content_type'] ?? null) ? $old['content_type'] : '';
            if ('' !== $oldCt && $oldCt !== $new['content_type']) {
                ++$regressions;
                $findings[] = new AppFinding(
                    code: 'KGN-SHADOW-CONTENT-TYPE-CHANGED',
                    severity: 'error',
                    who: 'Kongono Behavioral Equivalence Prover',
                    what: sprintf('`GET %s` changed Content-Type from `%s` to `%s`.', $path, $oldCt, $new['content_type']),
                    why: 'Changing media type breaks API/browser consumers expecting the original representation.',
                    where: 'GET ' . $path,
                    howToFix: [
                        sprintf('Restore `%s` response format for `GET %s`.', $oldCt, $path),
                    ],
                );
            }

            if (is_array($old['json_shape'] ?? null) && null !== $new['json_shape']) {
                $diffs = $this->diffJsonShapes($old['json_shape'], $new['json_shape'], '');
                if ([] !== $diffs) {
                    ++$regressions;
                    $findings[] = new AppFinding(
                        code: 'KGN-SHADOW-JSON-SHAPE-DRIFT',
                        severity: 'error',
                        who: 'Kongono Behavioral Equivalence Prover',
                        what: sprintf('`GET %s` JSON response contract drifted: %s.', $path, implode('; ', array_slice($diffs, 0, 5))),
                        why: 'Removed JSON keys or changed value types break frontend components and typed SDKs.',
                        where: 'GET ' . $path,
                        howToFix: [
                            'Restore the removed/changed JSON fields or update consumers and re-record baseline (`kongo shadow:record`).',
                        ],
                    );
                }
            }

            if (is_array($old['html_outline'] ?? null) && null !== $new['html_outline']) {
                $oldTitle = is_string($old['html_outline']['title'] ?? null) ? $old['html_outline']['title'] : '';
                $oldForms = is_int($old['html_outline']['forms'] ?? null) ? $old['html_outline']['forms'] : 0;
                $oldHeadings = is_array($old['html_outline']['headings'] ?? null) ? $old['html_outline']['headings'] : [];
                $newOutline = $new['html_outline'];
                if ($oldTitle !== $newOutline['title'] || $oldForms !== $newOutline['forms'] || $oldHeadings !== $newOutline['headings']) {
                    $findings[] = new AppFinding(
                        code: 'KGN-SHADOW-HTML-DRIFT',
                        severity: 'warning',
                        who: 'Kongono Behavioral Equivalence Prover',
                        what: sprintf(
                            '`GET %s` HTML landmark structure changed (title: `%s` -> `%s`, forms: %d -> %d).',
                            $path,
                            $oldTitle,
                            $newOutline['title'],
                            $oldForms,
                            $newOutline['forms'],
                        ),
                        why: 'Template refactor altered page title, headings, or form count compared to baseline.',
                        where: 'GET ' . $path,
                        howToFix: [
                            'Review template changes and run `kongo shadow:record` if the UI change is intentional.',
                        ],
                    );
                }
            }

            $oldQueries = is_int($old['query_count'] ?? null) ? $old['query_count'] : 0;
            if ($new['query_count'] > $oldQueries) {
                $findings[] = new AppFinding(
                    code: 'KGN-SHADOW-QUERY-REGRESSION',
                    severity: 'warning',
                    who: 'Kongono Behavioral Equivalence Prover',
                    what: sprintf('`GET %s` increased SQL query count from %d to %d (+%d).', $path, $oldQueries, $new['query_count'], $new['query_count'] - $oldQueries),
                    why: 'The refactor introduced additional database round-trips per request.',
                    where: 'GET ' . $path,
                    howToFix: [
                        'Check for newly introduced N+1 queries with `kongo perf` or eager-load via `BatchLoader`.',
                    ],
                );
            }
        }

        return [
            'equivalent' => 0 === $regressions && [] === $findings,
            'routes_compared' => count($paths),
            'regressions' => $regressions,
            'findings' => array_map(static fn(AppFinding $f): array => $f->toArray(), $findings),
        ];
    }

    /**
     * @param array<mixed> $data
     * @return array<string, mixed>
     */
    private function extractJsonShape(array $data, int $depth = 0): array
    {
        if ($depth > 5) {
            return ['_depth' => 'max'];
        }
        if (array_is_list($data)) {
            if ([] === $data) {
                return ['_list' => 'empty'];
            }
            $first = $data[0];

            return ['_list' => is_array($first) ? $this->extractJsonShape($first, $depth + 1) : get_debug_type($first)];
        }

        $shape = [];
        foreach ($data as $k => $v) {
            $ks = (string) $k;
            $shape[$ks] = is_array($v) ? $this->extractJsonShape($v, $depth + 1) : get_debug_type($v);
        }
        ksort($shape);

        return $shape;
    }

    /**
     * @param array<mixed> $old
     * @param array<mixed> $new
     * @return list<string>
     */
    private function diffJsonShapes(array $old, array $new, string $prefix): array
    {
        $diffs = [];
        foreach ($old as $k => $oldVal) {
            $ks = (string) $k;
            $path = '' === $prefix ? $ks : $prefix . '.' . $ks;
            if (!array_key_exists($k, $new)) {
                $diffs[] = sprintf('removed key `%s`', $path);
                continue;
            }
            $newVal = $new[$k];
            if (is_array($oldVal) && is_array($newVal)) {
                array_push($diffs, ...$this->diffJsonShapes($oldVal, $newVal, $path));
            } elseif ($oldVal !== $newVal) {
                $oldDesc = is_string($oldVal) ? $oldVal : 'object';
                $newDesc = is_string($newVal) ? $newVal : 'object';
                $diffs[] = sprintf('type of `%s` changed (%s -> %s)', $path, $oldDesc, $newDesc);
            }
        }

        return $diffs;
    }

    /** @return array{title: string, headings: list<string>, forms: int, links: int} */
    private function extractHtmlOutline(string $html): array
    {
        $title = '';
        if (1 === preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $tm)) {
            $title = trim((string) preg_replace('/\s+/', ' ', strip_tags($tm[1])));
        }
        $headings = [];
        if (preg_match_all('/<h[12]\b[^>]*>(.*?)<\/h[12]>/is', $html, $hm) > 0) {
            foreach (array_slice($hm[1], 0, 8) as $h) {
                $headings[] = trim((string) preg_replace('/\s+/', ' ', strip_tags($h)));
            }
        }
        $forms = (int) preg_match_all('/<form\b/i', $html);
        $links = (int) preg_match_all('/<a\b[^>]*\bhref\s*=/i', $html);

        return [
            'title' => $title,
            'headings' => $headings,
            'forms' => $forms,
            'links' => $links,
        ];
    }

    /** Determine whether a route path is safe for automated in-process GET probing. */
    public static function isSafeProbePath(string $path): bool
    {
        if (
            '' === $path
            || !str_starts_with($path, '/')
            || str_starts_with($path, '//')
            || str_contains($path, '\\')
            || str_contains($path, '..')
            || 1 === preg_match('/[\x00-\x1f\x7f]/', $path)
        ) {
            return false;
        }
        $pathOnly = explode('#', explode('?', $path, 2)[0], 2)[0];
        $decoded = strtolower(rawurldecode(rawurldecode($pathOnly)));
        if (str_contains($decoded, "\0") || str_contains($decoded, '\\') || 1 === preg_match('/[\x00-\x1f\x7f]/', $decoded)) {
            return false;
        }
        foreach (explode('/', $decoded) as $segment) {
            if ('.' === $segment || '..' === $segment) {
                return false;
            }
        }
        $collapsed = (string) preg_replace('#/+#', '/', '/' . ltrim($decoded, '/'));
        $canonical = '/' === $collapsed ? '/' : rtrim($collapsed, '/');

        foreach (['/_dev', '/_kongono', '/_ops', '/mcp'] as $blockedPrefix) {
            if ($canonical === $blockedPrefix || str_starts_with($canonical, $blockedPrefix . '/')) {
                return false;
            }
        }

        return !str_contains($canonical, 'logout');
    }

    /** @return list<string> */
    public static function discoverSafeGetPathsForApp(App $app): array
    {
        $paths = [];
        foreach ($app->router()->routes() as $route) {
            if (!in_array('GET', $route->methods, true) || str_contains($route->path, '{')) {
                continue;
            }
            if (!self::isSafeProbePath($route->path)) {
                continue;
            }
            if (!in_array($route->path, $paths, true)) {
                $paths[] = $route->path;
            }
        }

        return $paths;
    }

    /**
     * @param (Closure(Closure(string, float): void): Closure)|null $customBinder
     * @param Closure(string, float): void                          $listener
     * @return (Closure(): void)|null
     */
    public static function bindQueryListener(App $app, ?Closure $customBinder, Closure $listener): ?Closure
    {
        if (null !== $customBinder) {
            return $customBinder($listener);
        }
        if (!$app->container()->hasBinding(ConnectionInterface::class)) {
            return null;
        }
        try {
            $db = $app->container()->get(ConnectionInterface::class);
            if ($db instanceof PdoConnection) {
                return $db->listenQuery($listener);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
