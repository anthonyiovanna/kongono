<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

use Closure;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Http\App;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\SseEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Debug-only Kongono Dev Studio middleware (`/_kongono`).
 *
 * Completely dormant (fail-closed) when `$app->isDebug()` is false so production deployments
 * incur zero overhead and expose zero internal diagnostics.
 */
final readonly class DevDashboardMiddleware implements MiddlewareInterface
{
    private DevDashboardRenderer $renderer;

    /**
     * @param (Closure(string, ?string): string)|null                                       $oshieHandler        Optional callback ($question, $lesson) => reply
     * @param (Closure(string): string)|null                                                $hanteiHandler       Optional callback ($diagnostic) => diagnosis
     * @param (Closure(string): array<string, mixed>)|null                                  $chieHandler         Optional preview-only builder callback ($instruction) => diff payload
     * @param (Closure(string, ?string, Closure(string): void): array<string, mixed>)|null  $oshieStreamHandler  Optional SSE streaming callback ($question, $lesson, $onDelta) => completion payload
     * @param (Closure(string, Closure(string): void): array<string, mixed>)|null           $hanteiStreamHandler Optional SSE streaming callback ($diagnostic, $onDelta) => completion payload
     * @param (Closure(array{
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
     * }): array<string, mixed>)|null                                                       $hanteiTraceHandler  Optional callback diagnosing a captured DevInspector trace
     */
    public function __construct(
        private App $app,
        private DevInspector $inspector,
        private string $prefix = '/_kongono',
        private ?Closure $oshieHandler = null,
        private ?Closure $hanteiHandler = null,
        private ?Closure $chieHandler = null,
        private bool $loopbackOnly = true,
        private ?Closure $oshieStreamHandler = null,
        private ?Closure $hanteiStreamHandler = null,
        private ?Closure $hanteiTraceHandler = null,
    ) {
        $this->renderer = new DevDashboardRenderer();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->app->isDebug()) {
            return $handler->handle($request);
        }

        $path = '/' . trim($request->getUri()->getPath(), '/');
        $base = '/' . trim($this->prefix, '/');
        $method = strtoupper($request->getMethod());
        $isDevRoute = $path === $base || str_starts_with($path, $base . '/');

        if ($isDevRoute) {
            if ($this->loopbackOnly && !$this->isLoopbackClient($request)) {
                return Response::json(['error' => 'Kongono Dev Studio is restricted to loopback clients.'], 403);
            }
            if ('POST' === $method && !$this->isSameOriginMutatingRequest($request)) {
                return Response::json(['error' => 'Cross-site Dev Studio mutation blocked (requires same-origin JSON or X-Kongono-Dev header).'], 403);
            }
        }

        if ($path === $base && 'GET' === $method) {
            return Response::html($this->renderer->render($this->app, $this->inspector, $base));
        }

        if ($path === $base . '/api/traces' && 'GET' === $method) {
            return Response::json($this->inspector->traces());
        }

        if ($path === $base . '/api/clear' && 'POST' === $method) {
            $this->inspector->clear();

            return Response::json(['cleared' => true]);
        }

        if (1 === preg_match('#\A' . preg_quote($base, '#') . '/api/replay/(\d+)\z#', $path, $replayMatch) && 'POST' === $method) {
            return $this->handleReplay((int) $replayMatch[1], $base);
        }

        if (1 === preg_match('#\A' . preg_quote($base, '#') . '/api/export-test/(\d+)\z#', $path, $exportMatch) && 'GET' === $method) {
            $trace = $this->inspector->findTrace((int) $exportMatch[1]);
            if (null === $trace) {
                return Response::json(['error' => 'Trace not found.'], 404);
            }
            $code = TestExporter::exportPhpUnitTest($trace);
            if (str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json')) {
                return Response::json(['trace_id' => $trace['id'], 'code' => $code]);
            }

            return Response::text($code);
        }

        if ($path === $base . '/api/oshie/stream' && 'POST' === $method) {
            if (null === $this->oshieStreamHandler) {
                return Response::json(['error' => 'Oshie streaming bridge is not configured on DevDashboardMiddleware.'], 501);
            }
            $decoded = json_decode((string) $request->getBody(), true);
            $question = is_array($decoded) && is_string($decoded['question'] ?? null) ? trim($decoded['question']) : '';
            $lesson = is_array($decoded) && is_string($decoded['lesson'] ?? null) && '' !== trim($decoded['lesson']) ? trim($decoded['lesson']) : null;
            $streamHandler = $this->oshieStreamHandler;

            return $this->streamCompanionSse(
                static fn(Closure $onDelta): array => $streamHandler($question, $lesson, $onDelta),
            );
        }

        if ($path === $base . '/api/oshie' && 'POST' === $method) {
            if (null === $this->oshieHandler) {
                return Response::json(['reply' => 'Oshie browser bridge is not configured on DevDashboardMiddleware.'], 501);
            }
            $decoded = json_decode((string) $request->getBody(), true);
            $question = is_array($decoded) && is_string($decoded['question'] ?? null) ? trim($decoded['question']) : '';
            $lesson = is_array($decoded) && is_string($decoded['lesson'] ?? null) && '' !== trim($decoded['lesson']) ? trim($decoded['lesson']) : null;

            return Response::json(['reply' => ($this->oshieHandler)($question, $lesson)]);
        }

        if ($path === $base . '/api/hantei/stream' && 'POST' === $method) {
            if (null === $this->hanteiStreamHandler) {
                return Response::json(['error' => 'Hantei streaming bridge is not configured on DevDashboardMiddleware.'], 501);
            }
            $decoded = json_decode((string) $request->getBody(), true);
            $diagnostic = is_array($decoded) && is_string($decoded['diagnostic'] ?? null) ? trim($decoded['diagnostic']) : '';
            $streamHandler = $this->hanteiStreamHandler;

            return $this->streamCompanionSse(
                static fn(Closure $onDelta): array => $streamHandler($diagnostic, $onDelta),
            );
        }

        if (1 === preg_match('#\A' . preg_quote($base, '#') . '/api/hantei/trace/(\d+)\z#', $path, $traceDiagMatch) && 'POST' === $method) {
            if (null === $this->hanteiTraceHandler) {
                return Response::json(['error' => 'Hantei trace diagnosis bridge is not configured on DevDashboardMiddleware.'], 501);
            }
            $trace = $this->inspector->findTrace((int) $traceDiagMatch[1]);
            if (null === $trace) {
                return Response::json(['error' => 'Trace not found.'], 404);
            }

            return Response::json(($this->hanteiTraceHandler)($trace));
        }

        if ($path === $base . '/api/hantei' && 'POST' === $method) {
            if (null === $this->hanteiHandler) {
                return Response::json(['diagnosis' => 'Hantei browser bridge is not configured on DevDashboardMiddleware.'], 501);
            }
            $decoded = json_decode((string) $request->getBody(), true);
            $diagnostic = is_array($decoded) && is_string($decoded['diagnostic'] ?? null) ? trim($decoded['diagnostic']) : '';

            return Response::json(['diagnosis' => ($this->hanteiHandler)($diagnostic)]);
        }

        if ($path === $base . '/api/chie' && 'POST' === $method) {
            if (null === $this->chieHandler) {
                return Response::json(['summary' => 'Chie browser preview bridge is not configured on DevDashboardMiddleware.', 'diffs' => []], 501);
            }
            $decoded = json_decode((string) $request->getBody(), true);
            $instruction = is_array($decoded) && is_string($decoded['instruction'] ?? null) ? trim($decoded['instruction']) : '';
            if ('' === $instruction) {
                return Response::json(['error' => 'Provide an "instruction" string for Chie preview.'], 422);
            }

            return Response::json(($this->chieHandler)($instruction));
        }

        $this->inspector->beginRequest();
        $start = hrtime(true);
        $rawQuery = $request->getUri()->getQuery();
        $rawHeaders = $request->getHeaders();
        $rawBody = (string) $request->getBody();

        try {
            $response = $handler->handle($request);
            $durationMs = (hrtime(true) - $start) / 1_000_000;
            $this->inspector->recordRequest(
                method: $request->getMethod(),
                path: $path,
                status: $response->getStatusCode(),
                durationMs: $durationMs,
                query: $rawQuery,
                headers: $rawHeaders,
                body: $rawBody,
            );

            return $response;
        } catch (Throwable $e) {
            $durationMs = (hrtime(true) - $start) / 1_000_000;
            $this->inspector->recordRequest(
                method: $request->getMethod(),
                path: $path,
                status: 500,
                durationMs: $durationMs,
                error: $e->getMessage(),
                query: $rawQuery,
                headers: $rawHeaders,
                body: $rawBody,
            );
            throw $e;
        }
    }

    /**
     * Stream companion token deltas over Server-Sent Events (SSE).
     *
     * Under real HTTP SAPIs (cli-server, fpm-fcgi, frankenphp), deltas flush over the wire as soon as
     * each provider chunk arrives; under `cli` (PHPUnit / TestClient), frames yield cleanly for buffering.
     *
     * @param Closure(Closure(string): void): array<string, mixed> $runner
     */
    private function streamCompanionSse(Closure $runner): Response
    {
        return Response::sse(static function () use ($runner): iterable {
            $liveFlush = 'cli' !== PHP_SAPI;
            /** @var list<SseEvent> $buffered */
            $buffered = [];

            try {
                $done = $runner(static function (string $delta) use ($liveFlush, &$buffered): void {
                    if ('' === $delta) {
                        return;
                    }
                    $frame = SseEvent::json(['text' => $delta], 'delta');
                    if ($liveFlush) {
                        echo $frame->format();
                        if (ob_get_level() > 0) {
                            @ob_flush();
                        }
                        if (function_exists('flush')) {
                            flush();
                        }

                        return;
                    }
                    $buffered[] = $frame;
                });

                foreach ($buffered as $frame) {
                    yield $frame;
                }
                yield SseEvent::json($done, 'done');
            } catch (Throwable $e) {
                foreach ($buffered as $frame) {
                    yield $frame;
                }
                yield SseEvent::json(['error' => SecretScrubber::scrub($e->getMessage())], 'error');
            }
        });
    }

    private function handleReplay(int $traceId, string $base): Response
    {
        $trace = $this->inspector->findTrace($traceId);
        if (null === $trace) {
            return Response::json(['error' => 'Trace not found.'], 404);
        }
        if (str_starts_with($trace['path'], $base)) {
            return Response::json(['error' => 'Recursive Dev Studio replay is forbidden.'], 400);
        }

        $replayHeaders = [];
        foreach ($trace['headers'] as $name => $val) {
            if ('[REDACTED]' !== $val) {
                $replayHeaders[$name] = $val;
            }
        }
        $uri = '' !== $trace['query'] ? $trace['path'] . '?' . $trace['query'] : $trace['path'];
        $replayedReq = Request::create($trace['method'], $uri, $replayHeaders, $trace['body']);

        $start = hrtime(true);
        $res = $this->app->handle($replayedReq);
        $durationMs = round((hrtime(true) - $start) / 1_000_000, 2);
        $rawBody = $res instanceof Response ? $res->bodyString() : (string) $res->getBody();

        return Response::json([
            'replayed_id' => $traceId,
            'method' => $trace['method'],
            'uri' => $uri,
            'original_status' => $trace['status'],
            'replay_status' => $res->getStatusCode(),
            'duration_ms' => $durationMs,
            'body' => substr(SecretScrubber::scrub($rawBody), 0, 4000),
        ]);
    }

    private function isLoopbackClient(ServerRequestInterface $request): bool
    {
        $server = $request->getServerParams();
        $remote = $server['REMOTE_ADDR'] ?? null;
        if (!is_string($remote) || '' === trim($remote)) {
            // Only permit missing REMOTE_ADDR for in-process CLI / TestClient invocations
            if ('cli' !== PHP_SAPI) {
                return false;
            }
        } elseif (!in_array(strtolower(trim($remote)), ['127.0.0.1', '::1', 'localhost', '::ffff:127.0.0.1'], true)) {
            return false;
        }

        // Defend against DNS rebinding: reject any non-loopback Host header or URI host
        $loopbackHosts = ['127.0.0.1', 'localhost', '[::1]', '::1'];
        $uriHost = strtolower(trim($request->getUri()->getHost()));
        if ('' !== $uriHost && !in_array($uriHost, $loopbackHosts, true)) {
            return false;
        }

        $hostHeader = trim($request->getHeaderLine('Host'));
        if ('' !== $hostHeader) {
            $parsedHost = parse_url('http://' . $hostHeader, PHP_URL_HOST);
            $normalizedHost = is_string($parsedHost) ? strtolower($parsedHost) : '';
            if ('' === $normalizedHost || !in_array($normalizedHost, $loopbackHosts, true)) {
                return false;
            }
        }

        return true;
    }

    private function isSameOriginMutatingRequest(ServerRequestInterface $request): bool
    {
        $fetchSite = strtolower(trim($request->getHeaderLine('Sec-Fetch-Site')));
        if ('' !== $fetchSite && !in_array($fetchSite, ['same-origin', 'none'], true)) {
            return false;
        }

        $origin = trim($request->getHeaderLine('Origin'));
        if ('' !== $origin) {
            $parsed = parse_url($origin);
            $originHost = is_array($parsed) && is_string($parsed['host'] ?? null) ? strtolower($parsed['host']) : '';
            $reqHost = strtolower($request->getUri()->getHost());
            $allowedHosts = $this->loopbackOnly
                ? ['127.0.0.1', 'localhost', '[::1]', '::1']
                : array_unique(array_filter([$reqHost, '127.0.0.1', 'localhost', '[::1]', '::1'], static fn(string $h): bool => '' !== $h));
            if ('' === $originHost || !in_array($originHost, $allowedHosts, true)) {
                return false;
            }
        }

        // Require either custom X-Kongono-Dev header or application/json Content-Type (forces CORS preflight, defeats simple <form>/text/plain CSRF)
        if ('1' === trim($request->getHeaderLine('X-Kongono-Dev'))) {
            return true;
        }

        return str_contains(strtolower($request->getHeaderLine('Content-Type')), 'application/json');
    }
}
