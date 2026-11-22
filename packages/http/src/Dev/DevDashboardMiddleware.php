<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

use Closure;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Http\App;
use Kongono\Http\Request;
use Kongono\Http\Response;
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
     * @param (Closure(string, ?string): string)|null            $oshieHandler  Optional callback ($question, $lesson) => reply
     * @param (Closure(string): string)|null                     $hanteiHandler Optional callback ($diagnostic) => diagnosis
     * @param (Closure(string): array<string, mixed>)|null       $chieHandler   Optional preview-only builder callback ($instruction) => diff payload
     */
    public function __construct(
        private App $app,
        private DevInspector $inspector,
        private string $prefix = '/_kongono',
        private ?Closure $oshieHandler = null,
        private ?Closure $hanteiHandler = null,
        private ?Closure $chieHandler = null,
        private bool $loopbackOnly = true,
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

        if ($path === $base . '/api/oshie' && 'POST' === $method) {
            if (null === $this->oshieHandler) {
                return Response::json(['reply' => 'Oshie browser bridge is not configured on DevDashboardMiddleware.'], 501);
            }
            $decoded = json_decode((string) $request->getBody(), true);
            $question = is_array($decoded) && is_string($decoded['question'] ?? null) ? trim($decoded['question']) : '';
            $lesson = is_array($decoded) && is_string($decoded['lesson'] ?? null) && '' !== trim($decoded['lesson']) ? trim($decoded['lesson']) : null;

            return Response::json(['reply' => ($this->oshieHandler)($question, $lesson)]);
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
            // In-process TestClient / CLI calls without a network socket
            return true;
        }

        return in_array(strtolower(trim($remote)), ['127.0.0.1', '::1', 'localhost', '::ffff:127.0.0.1'], true);
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
            $allowedHosts = array_unique(array_filter([$reqHost, '127.0.0.1', 'localhost', '::1'], static fn(string $h): bool => '' !== $h));
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
