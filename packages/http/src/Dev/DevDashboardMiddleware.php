<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

use Closure;
use Kongono\Core\Config;
use Kongono\Core\Dev\DevMailboxInterface;
use Kongono\Core\Dev\FileDevMailbox;
use Kongono\Core\Diagnostics\IncidentStoreInterface;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Http\App;
use Kongono\Http\Brand\KongonoBrand;
use Kongono\Http\Request;
use Kongono\Http\Response;
use Kongono\Http\SseEvent;
use Kongono\Http\Stream;
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
    private DevRateLimiter $aiRateLimiter;

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
     *     memory_delta_kb?: float,
     *     response_headers?: array<string, string>,
     *     response_body?: string,
     *     queries: list<array{sql: string, ms: float}>,
     *     n_plus_one: list<array{pattern: string, count: int}>,
     *     error: ?string
     * }): array<string, mixed>)|null                                                       $hanteiTraceHandler    Optional callback diagnosing a captured DevInspector trace
     * @param (Closure(): array<string, mixed>)|null                                          $proofsHandler         Optional callback running Kongono MasterProver across all 5 proof engines
     * @param (Closure(string): array<string, mixed>)|null                                    $incidentReplayHandler Optional callback replaying a recorded `KGN-...` incident in-process
     * @param (Closure(string): array<string, mixed>)|null                                    $incidentTestHandler   Optional callback generating a PHPUnit 13 regression test preview for an incident
     * @param (Closure(): array<string, mixed>)|null                                          $schemaHandler         Optional read-only database schema inspection callback
     * @param (Closure(ServerRequestInterface): bool)|null                                    $authorizer            Optional authentication/authorization gate for Dev Studio routes
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
        private ?Closure $proofsHandler = null,
        private ?Closure $incidentReplayHandler = null,
        private ?Closure $incidentTestHandler = null,
        private bool $hotReload = false,
        private ?Closure $schemaHandler = null,
        private ?Closure $authorizer = null,
        private int $aiMaxRequestsPerMinute = 30,
        private bool $devHud = false,
    ) {
        $this->renderer = new DevDashboardRenderer();
        $this->aiRateLimiter = new DevRateLimiter();
        if ('cli' !== PHP_SAPI && $this->app->isDebug()) {
            $this->inspector->useStorageFile(
                $this->app->basePath() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'traces.json',
            );
        }
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
            if (null !== $this->authorizer && !($this->authorizer)($request)) {
                return Response::json(['error' => 'Unauthorized access to Kongono Dev Studio.'], 403);
            }
            if ('POST' === $method && !$this->isSameOriginMutatingRequest($request)) {
                return Response::json(['error' => 'Cross-site Dev Studio mutation blocked (requires same-origin JSON or X-Kongono-Dev header).'], 403);
            }
        }

        if ($path === $base && 'GET' === $method) {
            return Response::html($this->renderer->render($this->app, $this->inspector, $base), 200, [
                'X-Frame-Options' => 'DENY',
                'X-Content-Type-Options' => 'nosniff',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Content-Security-Policy' => "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'",
                'Cache-Control' => 'no-store',
            ]);
        }

        $apiHeaders = [
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];

        if ($path === $base . '/assets/studio.css' && 'GET' === $method) {
            return Response::text($this->renderer->studioStylesheet(), 200, [
                'Content-Type' => 'text/css; charset=utf-8',
                'Cache-Control' => 'no-store',
                'X-Content-Type-Options' => 'nosniff',
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ]);
        }

        if ($path === $base . '/assets/studio.js' && 'GET' === $method) {
            return Response::text($this->renderer->studioScript(), 200, [
                'Content-Type' => 'application/javascript; charset=utf-8',
                'Cache-Control' => 'no-store',
                'X-Content-Type-Options' => 'nosniff',
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ]);
        }

        if ($path === $base . '/assets/dev-hud.css' && 'GET' === $method) {
            return Response::text($this->renderer->devHudStylesheet(), 200, [
                'Content-Type' => 'text/css; charset=utf-8',
                'Cache-Control' => 'no-store',
                'X-Content-Type-Options' => 'nosniff',
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ]);
        }

        if ($path === $base . '/assets/dev-hud.js' && 'GET' === $method) {
            return Response::text($this->renderer->devHudScript(), 200, [
                'Content-Type' => 'application/javascript; charset=utf-8',
                'Cache-Control' => 'no-store',
                'X-Content-Type-Options' => 'nosniff',
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ]);
        }

        if ($path === $base . '/api/runtime' && 'GET' === $method) {
            return Response::json($this->collectRuntimeInfo(), 200, $apiHeaders);
        }

        if ($path === $base . '/api/try-route' && 'POST' === $method) {
            return $this->handleTryRoute($request, $base);
        }

        if ($path === $base . '/assets/dev-error.js' && 'GET' === $method) {
            return Response::text(self::devErrorScript(), 200, [
                'Content-Type' => 'application/javascript; charset=utf-8',
                'Cache-Control' => 'no-store',
                'X-Content-Type-Options' => 'nosniff',
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ]);
        }

        if ($path === $base . '/assets/hot-reload.js' && 'GET' === $method) {
            return Response::text(DevHotReloadWatcher::clientScript($base), 200, [
                'Content-Type' => 'application/javascript; charset=utf-8',
                'Cache-Control' => 'no-store',
                'X-Content-Type-Options' => 'nosniff',
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ]);
        }

        if ($path === $base . '/api/reload-token' && 'GET' === $method) {
            return Response::json((new DevHotReloadWatcher())->fingerprint($this->app->basePath()), 200, $apiHeaders);
        }

        if ($path === $base . '/live-reload' && 'GET' === $method) {
            $state = (new DevHotReloadWatcher())->fingerprint($this->app->basePath());
            $sseBody = "retry: 500\nevent: state\ndata: "
                . json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
                . "\n\n";

            return Response::text($sseBody, 200, [
                'Content-Type' => 'text/event-stream; charset=utf-8',
                'Cache-Control' => 'no-store',
                'X-Content-Type-Options' => 'nosniff',
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ]);
        }

        if ($path === $base . '/api/mailbox' && 'GET' === $method) {
            return Response::json(['messages' => $this->resolveDevMailbox()->messages()], 200, $apiHeaders);
        }

        if ($path === $base . '/api/mailbox/clear' && 'POST' === $method) {
            $this->resolveDevMailbox()->clear();

            return Response::json(['cleared' => true], 200, $apiHeaders);
        }

        if ($path === $base . '/api/schema' && 'GET' === $method) {
            if (null === $this->schemaHandler) {
                return Response::json(['connected' => false, 'dialect' => null, 'tables' => []], 200, $apiHeaders);
            }

            return Response::json(($this->schemaHandler)(), 200, $apiHeaders);
        }

        if ($path === $base . '/assets/favicon.svg' && 'GET' === $method) {
            return Response::text(KongonoBrand::faviconSvg(), 200, [
                'Content-Type' => 'image/svg+xml; charset=utf-8',
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        if ($path === $base . '/api/traces' && 'GET' === $method) {
            return Response::json($this->inspector->traces(), 200, $apiHeaders);
        }

        if ($path === $base . '/api/clear' && 'POST' === $method) {
            $this->inspector->clear();

            return Response::json(['cleared' => true], 200, $apiHeaders);
        }

        if (1 === preg_match('#\A' . preg_quote($base, '#') . '/api/replay/(\d+)\z#', $path, $replayMatch) && 'POST' === $method) {
            return $this->handleReplay((int) $replayMatch[1], $base);
        }

        if (1 === preg_match('#\A' . preg_quote($base, '#') . '/api/export-test/(\d+)\z#', $path, $exportMatch) && 'GET' === $method) {
            $trace = $this->inspector->findTrace((int) $exportMatch[1]);
            if (null === $trace) {
                return Response::json(['error' => 'Trace not found.'], 404, $apiHeaders);
            }
            $code = TestExporter::exportPhpUnitTest($trace);
            if (str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json')) {
                return Response::json(['trace_id' => $trace['id'], 'code' => $code], 200, $apiHeaders);
            }

            return Response::text($code, 200, $apiHeaders);
        }

        if ($path === $base . '/api/oshie/stream' && 'POST' === $method) {
            if (null !== ($limited = $this->enforceAiRateLimit($request, 'oshie', $apiHeaders))) {
                return $limited;
            }
            if (null === $this->oshieStreamHandler) {
                return Response::json(['error' => 'Oshie streaming bridge is not configured on DevDashboardMiddleware.'], 501, $apiHeaders);
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
            if (null !== ($limited = $this->enforceAiRateLimit($request, 'oshie', $apiHeaders))) {
                return $limited;
            }
            if (null === $this->oshieHandler) {
                return Response::json(['reply' => 'Oshie browser bridge is not configured on DevDashboardMiddleware.'], 501, $apiHeaders);
            }
            $decoded = json_decode((string) $request->getBody(), true);
            $question = is_array($decoded) && is_string($decoded['question'] ?? null) ? trim($decoded['question']) : '';
            $lesson = is_array($decoded) && is_string($decoded['lesson'] ?? null) && '' !== trim($decoded['lesson']) ? trim($decoded['lesson']) : null;

            return Response::json(['reply' => ($this->oshieHandler)($question, $lesson)], 200, $apiHeaders);
        }

        if ($path === $base . '/api/hantei/stream' && 'POST' === $method) {
            if (null !== ($limited = $this->enforceAiRateLimit($request, 'hantei', $apiHeaders))) {
                return $limited;
            }
            if (null === $this->hanteiStreamHandler) {
                return Response::json(['error' => 'Hantei streaming bridge is not configured on DevDashboardMiddleware.'], 501, $apiHeaders);
            }
            $decoded = json_decode((string) $request->getBody(), true);
            $diagnostic = is_array($decoded) && is_string($decoded['diagnostic'] ?? null) ? trim($decoded['diagnostic']) : '';
            $streamHandler = $this->hanteiStreamHandler;

            return $this->streamCompanionSse(
                static fn(Closure $onDelta): array => $streamHandler($diagnostic, $onDelta),
            );
        }

        if (1 === preg_match('#\A' . preg_quote($base, '#') . '/api/hantei/trace/(\d+)\z#', $path, $traceDiagMatch) && 'POST' === $method) {
            if (null !== ($limited = $this->enforceAiRateLimit($request, 'hantei', $apiHeaders))) {
                return $limited;
            }
            if (null === $this->hanteiTraceHandler) {
                return Response::json(['error' => 'Hantei trace diagnosis bridge is not configured on DevDashboardMiddleware.'], 501, $apiHeaders);
            }
            $trace = $this->inspector->findTrace((int) $traceDiagMatch[1]);
            if (null === $trace) {
                return Response::json(['error' => 'Trace not found.'], 404, $apiHeaders);
            }

            return Response::json(($this->hanteiTraceHandler)($trace), 200, $apiHeaders);
        }

        if ($path === $base . '/api/hantei' && 'POST' === $method) {
            if (null !== ($limited = $this->enforceAiRateLimit($request, 'hantei', $apiHeaders))) {
                return $limited;
            }
            if (null === $this->hanteiHandler) {
                return Response::json(['diagnosis' => 'Hantei browser bridge is not configured on DevDashboardMiddleware.'], 501, $apiHeaders);
            }
            $decoded = json_decode((string) $request->getBody(), true);
            $diagnostic = is_array($decoded) && is_string($decoded['diagnostic'] ?? null) ? trim($decoded['diagnostic']) : '';

            return Response::json(['diagnosis' => ($this->hanteiHandler)($diagnostic)], 200, $apiHeaders);
        }

        if ($path === $base . '/api/chie' && 'POST' === $method) {
            if (null !== ($limited = $this->enforceAiRateLimit($request, 'chie', $apiHeaders))) {
                return $limited;
            }
            if (null === $this->chieHandler) {
                return Response::json(['summary' => 'Chie browser preview bridge is not configured on DevDashboardMiddleware.', 'diffs' => []], 501, $apiHeaders);
            }
            $decoded = json_decode((string) $request->getBody(), true);
            $instruction = is_array($decoded) && is_string($decoded['instruction'] ?? null) ? trim($decoded['instruction']) : '';
            if ('' === $instruction) {
                return Response::json(['error' => 'Provide an "instruction" string for Chie preview.'], 422, $apiHeaders);
            }

            return Response::json(($this->chieHandler)($instruction), 200, $apiHeaders);
        }

        if ($path === $base . '/api/proofs' && 'POST' === $method) {
            if (null === $this->proofsHandler) {
                return Response::json(['error' => 'Proof Command Center bridge is not configured on DevDashboardMiddleware.'], 501, $apiHeaders);
            }

            return Response::json(($this->proofsHandler)(), 200, $apiHeaders);
        }

        if ($path === $base . '/api/incidents' && 'GET' === $method) {
            $records = [];
            if ($this->app->container()->hasBinding(IncidentStoreInterface::class)) {
                $store = $this->app->container()->get(IncidentStoreInterface::class);
                if ($store instanceof IncidentStoreInterface) {
                    $records = array_map(static fn($r): array => $r->toArray(), $store->recent(20));
                }
            }

            return Response::json(['incidents' => $records], 200, $apiHeaders);
        }

        if (1 === preg_match('#\A' . preg_quote($base, '#') . '/api/incidents/(KGN-\d{8}-[A-F0-9]{8})/replay\z#', $path, $incReplayMatch) && 'POST' === $method) {
            if (null === $this->incidentReplayHandler) {
                return Response::json(['error' => 'Incident replay bridge is not configured on DevDashboardMiddleware.'], 501, $apiHeaders);
            }

            return Response::json(($this->incidentReplayHandler)($incReplayMatch[1]), 200, $apiHeaders);
        }

        if (1 === preg_match('#\A' . preg_quote($base, '#') . '/api/incidents/(KGN-\d{8}-[A-F0-9]{8})/test\z#', $path, $incTestMatch) && 'GET' === $method) {
            if (null === $this->incidentTestHandler) {
                return Response::json(['error' => 'Incident test preview bridge is not configured on DevDashboardMiddleware.'], 501, $apiHeaders);
            }

            return Response::json(($this->incidentTestHandler)($incTestMatch[1]), 200, $apiHeaders);
        }

        $this->inspector->beginRequest();
        $start = hrtime(true);
        $memBefore = memory_get_usage();
        $rawQuery = $request->getUri()->getQuery();
        $rawHeaders = $request->getHeaders();
        $bodyStream = $request->getBody();
        $bodySize = $bodyStream->getSize();
        if ($bodyStream->isSeekable()) {
            $bodyStream->rewind();
            $rawBody = $bodyStream->read(16_384);
            $bodyStream->rewind();
        } elseif (null !== $bodySize && $bodySize <= 16_384) {
            $rawBody = $bodyStream->getContents();
            $request = $request->withBody(Stream::fromString($rawBody));
        } else {
            $rawBody = '';
        }

        try {
            $response = $handler->handle($request);
            $durationMs = (hrtime(true) - $start) / 1_000_000;
            $memoryDeltaKb = (memory_get_usage() - $memBefore) / 1024;
            $responseBodyPreview = $this->peekResponseBody($response);
            $traceId = $this->inspector->recordRequest(
                method: $request->getMethod(),
                path: $path,
                status: $response->getStatusCode(),
                durationMs: $durationMs,
                query: $rawQuery,
                headers: $rawHeaders,
                body: $rawBody,
                memoryDeltaKb: $memoryDeltaKb,
                responseHeaders: $response->getHeaders(),
                responseBody: $responseBodyPreview,
            );

            if ($this->devHud && !$isDevRoute && $response->getStatusCode() < 500) {
                $recordedTrace = $this->inspector->findTrace($traceId);
                $sqlCount = null !== $recordedTrace ? count($recordedTrace['queries']) : 0;
                $n1Count = null !== $recordedTrace ? count($recordedTrace['n_plus_one']) : 0;
                $response = $this->injectDevHud(
                    $response,
                    $base,
                    $traceId,
                    $response->getStatusCode(),
                    round($durationMs, 2),
                    round($memoryDeltaKb, 1),
                    $sqlCount,
                    $n1Count,
                );
            }

            if ($this->hotReload && !$isDevRoute && 200 === $response->getStatusCode()) {
                $response = $this->injectHotReloadScript($response, $base);
            }

            return $response;
        } catch (Throwable $e) {
            $durationMs = (hrtime(true) - $start) / 1_000_000;
            $memoryDeltaKb = (memory_get_usage() - $memBefore) / 1024;
            $this->inspector->recordRequest(
                method: $request->getMethod(),
                path: $path,
                status: 500,
                durationMs: $durationMs,
                error: $e->getMessage(),
                query: $rawQuery,
                headers: $rawHeaders,
                body: $rawBody,
                memoryDeltaKb: $memoryDeltaKb,
            );
            throw $e;
        }
    }

    private function peekResponseBody(ResponseInterface $response): string
    {
        if ('' !== trim($response->getHeaderLine('Content-Encoding'))) {
            return '';
        }
        $ct = strtolower($response->getHeaderLine('Content-Type'));
        $isTextual = '' === $ct
            || str_contains($ct, 'application/json')
            || str_contains($ct, '+json')
            || str_contains($ct, 'text/')
            || str_contains($ct, 'application/xml')
            || str_contains($ct, '+xml');
        if (!$isTextual) {
            return '';
        }
        $stream = $response->getBody();
        if (!$stream->isSeekable()) {
            return '';
        }
        $stream->rewind();
        $preview = $stream->read(8192);
        $stream->rewind();

        return $preview;
    }

    private function resolveDevMailbox(): DevMailboxInterface
    {
        if ($this->app->container()->hasBinding(DevMailboxInterface::class)) {
            $bound = $this->app->container()->get(DevMailboxInterface::class);
            if ($bound instanceof DevMailboxInterface) {
                return $bound;
            }
        }

        return new FileDevMailbox($this->app->basePath() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'dev' . DIRECTORY_SEPARATOR . 'mailbox.json');
    }

    private function injectHotReloadScript(ResponseInterface $response, string $base): ResponseInterface
    {
        $ct = strtolower($response->getHeaderLine('Content-Type'));
        if (!str_contains($ct, 'text/html')) {
            return $response;
        }
        if ('' !== trim($response->getHeaderLine('Content-Encoding'))) {
            return $response;
        }
        $stream = $response->getBody();
        if (!$stream->isSeekable()) {
            return $response;
        }
        $stream->rewind();
        $body = $stream->getContents();
        $pos = strripos($body, '</body>');
        if (false === $pos || str_contains($body, '/assets/hot-reload.js')) {
            $stream->rewind();

            return $response;
        }
        $tag = sprintf('<script src="%s/assets/hot-reload.js" defer></script>', htmlspecialchars($base, ENT_QUOTES, 'UTF-8'));
        $updated = substr($body, 0, $pos) . $tag . "\n" . substr($body, $pos);

        return $response->withoutHeader('Content-Length')->withBody(Stream::fromString($updated));
    }

    private function injectDevHud(
        ResponseInterface $response,
        string $base,
        int $traceId,
        int $status,
        float $durationMs,
        float $memoryDeltaKb,
        int $sqlCount,
        int $n1Count,
    ): ResponseInterface {
        $ct = strtolower($response->getHeaderLine('Content-Type'));
        if (!str_contains($ct, 'text/html')) {
            return $response;
        }
        if ('' !== trim($response->getHeaderLine('Content-Encoding'))) {
            return $response;
        }
        $stream = $response->getBody();
        if (!$stream->isSeekable()) {
            return $response;
        }
        $stream->rewind();
        $body = $stream->getContents();
        $bodyPos = strripos($body, '</body>');
        if (false === $bodyPos || str_contains($body, '/assets/dev-hud.js')) {
            $stream->rewind();

            return $response;
        }

        $safeBase = htmlspecialchars($base, ENT_QUOTES, 'UTF-8');
        $cssTag = sprintf('<link rel="stylesheet" href="%s/assets/dev-hud.css">', $safeBase);
        $scriptTag = sprintf(
            '<script src="%s/assets/dev-hud.js" defer data-base="%s" data-trace-id="%d" data-status="%d" data-ms="%s" data-mem="%s" data-sql="%d" data-n1="%d"></script>',
            $safeBase,
            $safeBase,
            $traceId,
            $status,
            htmlspecialchars(number_format($durationMs, 2, '.', ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(number_format($memoryDeltaKb, 1, '.', ''), ENT_QUOTES, 'UTF-8'),
            $sqlCount,
            $n1Count,
        );

        $headPos = strripos($body, '</head>');
        if (false !== $headPos && $headPos < $bodyPos) {
            $body = substr($body, 0, $headPos) . $cssTag . "\n" . substr($body, $headPos);
            $bodyPos = strripos($body, '</body>');
            if (false === $bodyPos) {
                return $response->withoutHeader('Content-Length')->withBody(Stream::fromString($body));
            }
            $updated = substr($body, 0, $bodyPos) . $scriptTag . "\n" . substr($body, $bodyPos);
        } else {
            $updated = substr($body, 0, $bodyPos) . $cssTag . "\n" . $scriptTag . "\n" . substr($body, $bodyPos);
        }

        return $response->withoutHeader('Content-Length')->withBody(Stream::fromString($updated));
    }

    private function handleTryRoute(ServerRequestInterface $request, string $base): Response
    {
        $apiHeaders = [
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];
        $decoded = json_decode((string) $request->getBody(), true);
        if (!is_array($decoded)) {
            return Response::json(['error' => 'Invalid JSON request payload.'], 422, $apiHeaders);
        }

        $method = strtoupper(is_string($decoded['method'] ?? null) ? trim($decoded['method']) : 'GET');
        $allowedMethods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        if (!in_array($method, $allowedMethods, true)) {
            return Response::json(['error' => 'Unsupported HTTP method.'], 422, $apiHeaders);
        }

        $rawPath = is_string($decoded['path'] ?? null) ? trim($decoded['path']) : '';
        if ('' === $rawPath || !str_starts_with($rawPath, '/') || str_contains($rawPath, "\r") || str_contains($rawPath, "\n")) {
            return Response::json(['error' => 'Path must start with "/" and contain no control characters.'], 422, $apiHeaders);
        }
        $normalizedPath = '/' . ltrim(explode('?', $rawPath, 2)[0], '/');
        if ($normalizedPath === $base || str_starts_with($normalizedPath, $base . '/')) {
            return Response::json(['error' => 'Recursive Dev Studio route execution is forbidden.'], 400, $apiHeaders);
        }

        $query = is_string($decoded['query'] ?? null) ? ltrim(trim($decoded['query']), '?') : '';
        if ('' === $query && str_contains($rawPath, '?')) {
            $query = explode('?', $rawPath, 2)[1];
        }
        $uri = '' !== $query ? $normalizedPath . '?' . $query : $normalizedPath;

        $customHeaders = [];
        if (is_array($decoded['headers'] ?? null)) {
            foreach ($decoded['headers'] as $name => $val) {
                if (!is_string($name) || !is_string($val)) {
                    continue;
                }
                $cleanName = trim($name);
                if ('' === $cleanName || 1 === preg_match('/[\r\n:]/', $cleanName) || 1 === preg_match('/[\r\n]/', $val)) {
                    continue;
                }
                $customHeaders[$cleanName] = trim($val);
            }
        }

        $body = is_string($decoded['body'] ?? null) ? substr($decoded['body'], 0, 65_536) : '';
        if ('' !== $body) {
            $hasCt = false;
            foreach (array_keys($customHeaders) as $hn) {
                if ('content-type' === strtolower($hn)) {
                    $hasCt = true;
                    break;
                }
            }
            if (!$hasCt && (str_starts_with(ltrim($body), '{') || str_starts_with(ltrim($body), '['))) {
                $customHeaders['Content-Type'] = 'application/json';
            }
        }

        $tryRequest = Request::create($method, $uri, $customHeaders, $body);
        $start = hrtime(true);
        $memBefore = memory_get_usage();
        try {
            $res = $this->app->handle($tryRequest);
        } catch (Throwable $e) {
            return Response::json([
                'error' => SecretScrubber::scrub($e->getMessage()),
                'method' => $method,
                'uri' => $uri,
                'status' => 500,
                'duration_ms' => round((hrtime(true) - $start) / 1_000_000, 2),
                'memory_delta_kb' => round((memory_get_usage() - $memBefore) / 1024, 1),
            ], 200, $apiHeaders);
        }

        $durationMs = round((hrtime(true) - $start) / 1_000_000, 2);
        $memoryDeltaKb = round((memory_get_usage() - $memBefore) / 1024, 1);

        $safeResHeaders = [];
        foreach ($res->getHeaders() as $hName => $vals) {
            $lower = strtolower($hName);
            $joined = implode(', ', $vals);
            $sensitive = in_array($lower, ['set-cookie', 'authorization', 'x-api-key', 'x-csrf-token'], true)
                || SecretScrubber::isSensitiveKey($hName);
            $safeResHeaders[$hName] = $sensitive ? '[REDACTED]' : SecretScrubber::scrub($joined);
        }

        $rawResBody = $this->peekResponseBody($res);
        $scrubbedResBody = substr($this->scrubTextualPayload($rawResBody), 0, 8000);
        $recentTraces = $this->inspector->traces();
        $traceId = isset($recentTraces[0]) ? $recentTraces[0]['id'] : null;

        return Response::json([
            'method' => $method,
            'uri' => $uri,
            'status' => $res->getStatusCode(),
            'duration_ms' => $durationMs,
            'memory_delta_kb' => $memoryDeltaKb,
            'trace_id' => $traceId,
            'response_headers' => $safeResHeaders,
            'response_body' => $scrubbedResBody,
        ], 200, $apiHeaders);
    }

    private function scrubTextualPayload(string $body): string
    {
        $trimmed = ltrim($body);
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $reencoded = json_encode(
                    SecretScrubber::scrubArray($decoded),
                    JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE,
                );
                if (is_string($reencoded)) {
                    return $reencoded;
                }
            }
        }

        return SecretScrubber::scrub($body);
    }

    /** @return array<string, mixed> */
    private function collectRuntimeInfo(): array
    {
        $opcacheEnabled = false;
        $jitEnabled = false;
        if (function_exists('opcache_get_status')) {
            $opStatus = @opcache_get_status(false);
            if (is_array($opStatus)) {
                $opcacheEnabled = true === ($opStatus['opcache_enabled'] ?? false);
                $jit = $opStatus['jit'] ?? null;
                $jitEnabled = is_array($jit) && true === ($jit['enabled'] ?? false);
            }
        }

        $requiredExts = ['curl', 'dom', 'json', 'mbstring', 'openssl', 'pdo', 'pdo_pgsql', 'pdo_sqlite', 'sodium', 'zlib'];
        $extensions = array_map(
            static fn(string $ext): array => ['name' => $ext, 'loaded' => extension_loaded($ext)],
            $requiredExts,
        );

        $container = $this->app->container();
        $storageFile = $this->inspector->storageFile();

        $subsystems = [
            ['name' => 'Debug Mode', 'active' => $this->app->isDebug(), 'detail' => 'APP_DEBUG=true (auto-disabled in production)'],
            ['name' => 'Hot Reload', 'active' => $this->hotReload, 'detail' => $this->hotReload ? 'Zero-build CSS swap & reload enabled' : 'Pass hotReload: true to DevDashboardMiddleware'],
            ['name' => 'Floating Dev HUD', 'active' => $this->devHud, 'detail' => $this->devHud ? 'Strict-CSP bottom-right HUD pill active on HTML pages' : 'Pass devHud: true to DevDashboardMiddleware'],
            ['name' => 'Trace Persistence', 'active' => null !== $storageFile, 'detail' => null !== $storageFile ? 'File ring-buffer (' . basename($storageFile) . ')' : 'In-memory ring-buffer (CLI / single worker)'],
            ['name' => 'Dev Mailbox', 'active' => $container->hasBinding(DevMailboxInterface::class), 'detail' => $container->hasBinding(DevMailboxInterface::class) ? 'DevMailboxInterface bound' : 'Fallback storage/dev/mailbox.json'],
            ['name' => 'Incident Time-Machine', 'active' => $container->hasBinding(IncidentStoreInterface::class), 'detail' => $container->hasBinding(IncidentStoreInterface::class) ? 'Recording KGN-* incidents' : 'Enable with $app->recordIncidents()'],
            ['name' => 'Proof Center', 'active' => null !== $this->proofsHandler, 'detail' => null !== $this->proofsHandler ? 'All 5 proof engines wired' : 'Wire proofsHandler or use DevStudioCompanions'],
            ['name' => 'Schema Explorer', 'active' => null !== $this->schemaHandler, 'detail' => null !== $this->schemaHandler ? 'Read-only schema inspector wired' : 'Wire schemaHandler to inspect DB schema'],
            ['name' => 'AI Companions', 'active' => null !== $this->oshieHandler || null !== $this->hanteiHandler || null !== $this->chieHandler, 'detail' => sprintf('Oshie:%s · Chie:%s · Hantei:%s', null !== $this->oshieHandler ? 'on' : 'off', null !== $this->chieHandler ? 'on' : 'off', null !== $this->hanteiHandler ? 'on' : 'off')],
            ['name' => 'Database', 'active' => $container->hasBinding('Kongono\\Database\\ConnectionInterface'), 'detail' => $container->hasBinding('Kongono\\Database\\ConnectionInterface') ? 'ConnectionInterface registered' : 'Not registered'],
            ['name' => 'Cache', 'active' => $container->hasBinding('Kongono\\Core\\Cache\\CacheInterface'), 'detail' => $container->hasBinding('Kongono\\Core\\Cache\\CacheInterface') ? 'CacheInterface registered' : 'Not registered'],
            ['name' => 'Storage', 'active' => $container->hasBinding('Kongono\\Storage\\StorageInterface'), 'detail' => $container->hasBinding('Kongono\\Storage\\StorageInterface') ? 'StorageInterface registered' : 'Not registered'],
            ['name' => 'Queue', 'active' => $container->hasBinding('Kongono\\Jobs\\Queue'), 'detail' => $container->hasBinding('Kongono\\Jobs\\Queue') ? 'Queue registered' : 'Not registered'],
            ['name' => 'Mailer', 'active' => $container->hasBinding('Kongono\\Mail\\Mailer'), 'detail' => $container->hasBinding('Kongono\\Mail\\Mailer') ? 'Mailer registered' : 'Not registered'],
            ['name' => 'Metrics & Health', 'active' => $container->hasBinding('Kongono\\Core\\Health\\HealthCheck') || $container->hasBinding('Kongono\\Core\\Telemetry\\Metrics'), 'detail' => sprintf('HealthCheck:%s · Metrics:%s', $container->hasBinding('Kongono\\Core\\Health\\HealthCheck') ? 'on' : 'off', $container->hasBinding('Kongono\\Core\\Telemetry\\Metrics') ? 'on' : 'off')],
        ];

        $safeEnvKeys = ['APP_ENV', 'APP_DEBUG', 'APP_URL', 'LOG_LEVEL', 'CACHE_DRIVER', 'QUEUE_DRIVER', 'MAIL_TRANSPORT', 'SESSION_SECURE', 'OLLAMA_HOST'];
        $safeEnv = [];
        foreach ($safeEnvKeys as $key) {
            $val = Config::env($key);
            $safeEnv[] = [
                'key' => $key,
                'value' => null !== $val && '' !== trim($val) ? SecretScrubber::scrub(trim($val)) : null,
            ];
        }

        $secretEnvKeys = ['APP_KEY', 'DATABASE_URL', 'REDIS_URL', 'OPENAI_API_KEY', 'ANTHROPIC_API_KEY', 'GEMINI_API_KEY', 'GROQ_API_KEY', 'RESEND_API_KEY'];
        $secretEnv = [];
        foreach ($secretEnvKeys as $key) {
            $val = Config::env($key);
            $secretEnv[] = [
                'key' => $key,
                'configured' => null !== $val && '' !== trim($val),
            ];
        }

        return [
            'php' => [
                'version' => PHP_VERSION,
                'php_8513_safe' => version_compare(PHP_VERSION, '8.5.13', '>='),
                'sapi' => PHP_SAPI,
                'memory_limit' => ini_get('memory_limit'),
                'memory_usage_mb' => round(memory_get_usage(true) / 1_048_576, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1_048_576, 2),
                'opcache_enabled' => $opcacheEnabled,
                'jit_enabled' => $jitEnabled,
            ],
            'extensions' => $extensions,
            'subsystems' => $subsystems,
            'safe_env' => $safeEnv,
            'secret_env' => $secretEnv,
        ];
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

    private static function devErrorScript(): string
    {
        return <<<'JS'
(() => {
  const base = typeof document.body.dataset.devBase === 'string' ? document.body.dataset.devBase : '/_kongono';
  const root = document.documentElement;

  // Theme preference (follows OS by default; 1-click toggle persists locally)
  try {
    const savedTheme = localStorage.getItem('kongono-dev-theme');
    if (savedTheme === 'light' || savedTheme === 'dark') {
      root.dataset.theme = savedTheme;
    }
  } catch {}

  const themeBtn = document.getElementById('kgn-theme-toggle');
  if (themeBtn) {
    themeBtn.addEventListener('click', () => {
      const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
      const current = root.dataset.theme || (prefersLight ? 'light' : 'dark');
      const next = current === 'dark' ? 'light' : 'dark';
      root.dataset.theme = next;
      try { localStorage.setItem('kongono-dev-theme', next); } catch {}
    });
  }

  async function copyText(text, btn) {
    if (!text) return;
    try {
      await navigator.clipboard.writeText(text);
    } catch {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      ta.remove();
    }
    const origNodes = Array.from(btn.childNodes);
    btn.textContent = 'Copied';
    setTimeout(() => {
      btn.textContent = '';
      origNodes.forEach((n) => btn.appendChild(n));
    }, 1500);
  }

  document.querySelectorAll('[data-copy-target]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const el = document.getElementById(btn.dataset.copyTarget);
      if (el) copyText(el.value || el.textContent || '', btn);
    });
  });

  document.querySelectorAll('[data-copy-text]').forEach((btn) => {
    btn.addEventListener('click', () => copyText(btn.dataset.copyText || '', btn));
  });

  const askBtn = document.getElementById('kgn-ask-hantei');
  const copyLlmBtn = document.getElementById('kgn-copy-llm');
  const outEl = document.getElementById('kgn-hantei-output');
  const llmEl = document.getElementById('kgn-llm-prompt');
  const hanteiTab = document.getElementById('kgn-tab-hantei');

  document.addEventListener('keydown', (e) => {
    if (e.metaKey || e.ctrlKey || e.altKey) return;
    const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
    if (e.key === 'c' && copyLlmBtn) {
      e.preventDefault();
      copyLlmBtn.click();
    } else if (e.key === 'h' && askBtn) {
      e.preventDefault();
      askBtn.click();
    }
  });

  if (!askBtn || !outEl || !llmEl) return;

  askBtn.addEventListener('click', async () => {
    if (hanteiTab) hanteiTab.checked = true;
    outEl.classList.add('is-visible');
    outEl.textContent = 'Asking Hantei to diagnose this exception...';
    outEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    const diagnostic = llmEl.value || '';

    try {
      const res = await fetch(base + '/api/hantei/stream', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'text/event-stream', 'X-Kongono-Dev': '1' },
        body: JSON.stringify({ diagnostic }),
      });

      if (res.status !== 501 && res.ok && res.body) {
        const reader = res.body.getReader();
        const decoder = new TextDecoder();
        let buf = '';
        let acc = '';
        let donePayload = null;
        while (true) {
          const { value, done } = await reader.read();
          if (done) break;
          buf += decoder.decode(value, { stream: true });
          const frames = buf.split('\n\n');
          buf = frames.pop() || '';
          for (const frame of frames) {
            let ev = 'message';
            const dataLines = [];
            for (const line of frame.split('\n')) {
              if (line.startsWith('event:')) ev = line.slice(6).trim();
              else if (line.startsWith('data:')) dataLines.push(line.slice(5).trim());
            }
            if (!dataLines.length) continue;
            const parsed = JSON.parse(dataLines.join('\n'));
            if (ev === 'delta' && parsed.text) {
              acc += parsed.text;
              outEl.textContent = acc;
            } else if (ev === 'done') {
              donePayload = parsed;
            } else if (ev === 'error') {
              throw new Error(parsed.error || 'Hantei stream error');
            }
          }
        }
        const text = acc || (donePayload && donePayload.diagnosis) || '';
        const footer = donePayload && donePayload.telemetry && donePayload.telemetry.footer ? '\n\n' + donePayload.telemetry.footer : '';
        outEl.textContent = text + footer;
        return;
      }

      const fallback = await fetch(base + '/api/hantei', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Kongono-Dev': '1' },
        body: JSON.stringify({ diagnostic }),
      });
      const json = await fallback.json();
      outEl.textContent = json.diagnosis || json.error || JSON.stringify(json, null, 2);
    } catch (err) {
      outEl.textContent = 'Hantei live diagnosis unavailable: ' + err.message;
    }
  });
})();
JS;
    }

    private function handleReplay(int $traceId, string $base): Response
    {
        $apiHeaders = [
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];
        $trace = $this->inspector->findTrace($traceId);
        if (null === $trace) {
            return Response::json(['error' => 'Trace not found.'], 404, $apiHeaders);
        }
        if ($trace['path'] === $base || str_starts_with($trace['path'], $base . '/')) {
            return Response::json(['error' => 'Recursive Dev Studio replay is forbidden.'], 400, $apiHeaders);
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
        ], 200, $apiHeaders);
    }

    private function isLoopbackClient(ServerRequestInterface $request): bool
    {
        $loopbackIps = ['127.0.0.1', '::1', 'localhost', '::ffff:127.0.0.1'];
        $server = $request->getServerParams();
        $remote = $server['REMOTE_ADDR'] ?? null;
        if (!is_string($remote) || '' === trim($remote)) {
            // Only permit missing REMOTE_ADDR for in-process CLI / TestClient invocations
            if ('cli' !== PHP_SAPI) {
                return false;
            }
        } elseif (!in_array(strtolower(trim($remote)), $loopbackIps, true)) {
            return false;
        }

        // Reject forwarded non-loopback clients when behind a local loopback reverse proxy
        $xff = trim($request->getHeaderLine('X-Forwarded-For'));
        if ('' !== $xff) {
            foreach (explode(',', $xff) as $hop) {
                if (!in_array(strtolower(trim($hop)), $loopbackIps, true)) {
                    return false;
                }
            }
        }
        $forwarded = trim($request->getHeaderLine('Forwarded'));
        if ('' !== $forwarded && 1 === preg_match_all('/for="?\[?([^";,\s\]]+)/i', $forwarded, $fm)) {
            foreach ($fm[1] as $hop) {
                if (!in_array(strtolower(trim($hop)), $loopbackIps, true)) {
                    return false;
                }
            }
        }

        // Defend against DNS rebinding: reject any non-loopback Host header or URI host
        $loopbackHosts = ['127.0.0.1', 'localhost', '[::1]', '::1'];
        $uriHost = strtolower(trim($request->getUri()->getHost()));
        if ('' !== $uriHost && !in_array($uriHost, $loopbackHosts, true)) {
            return false;
        }

        $hostHeader = trim($request->getHeaderLine('Host'));
        if ('' !== $hostHeader && 1 !== preg_match('/\A(?:\[::1\]|127\.0\.0\.1|localhost)(?::\d{1,5})?\z/i', $hostHeader)) {
            return false;
        }

        return true;
    }

    /** @param array<string, string> $apiHeaders */
    private function enforceAiRateLimit(ServerRequestInterface $request, string $companion, array $apiHeaders): ?Response
    {
        if ($this->aiMaxRequestsPerMinute <= 0) {
            return null;
        }
        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? 'local';
        $client = is_string($remote) && '' !== $remote ? $remote : 'local';
        $retryAfter = $this->aiRateLimiter->attempt('ai:' . $companion . ':' . $client, $this->aiMaxRequestsPerMinute, 60);
        if ($retryAfter > 0) {
            return Response::json(
                ['error' => sprintf('Dev Studio AI rate limit exceeded for %s. Try again in %d second(s).', $companion, $retryAfter)],
                429,
                [...$apiHeaders, 'Retry-After' => (string) $retryAfter],
            );
        }

        return null;
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
            $originScheme = is_array($parsed) && is_string($parsed['scheme'] ?? null) ? strtolower($parsed['scheme']) : '';
            $originHost = is_array($parsed) && is_string($parsed['host'] ?? null) ? strtolower($parsed['host']) : '';
            $originPort = is_array($parsed) && is_int($parsed['port'] ?? null) ? $parsed['port'] : null;
            if (('http' === $originScheme && 80 === $originPort) || ('https' === $originScheme && 443 === $originPort)) {
                $originPort = null;
            }
            $reqScheme = strtolower($request->getUri()->getScheme());
            $reqHost = strtolower($request->getUri()->getHost());
            $reqPort = $request->getUri()->getPort();
            if ('' === $reqHost) {
                $hostHeader = trim($request->getHeaderLine('Host'));
                if ('' !== $hostHeader) {
                    $parsedHost = parse_url('http://' . $hostHeader);
                    if (is_array($parsedHost) && is_string($parsedHost['host'] ?? null)) {
                        $reqHost = strtolower($parsedHost['host']);
                        $reqPort = is_int($parsedHost['port'] ?? null) ? $parsedHost['port'] : $reqPort;
                    }
                }
            }
            if (('http' === $reqScheme && 80 === $reqPort) || ('https' === $reqScheme && 443 === $reqPort)) {
                $reqPort = null;
            }
            $allowedHosts = $this->loopbackOnly
                ? ['127.0.0.1', 'localhost', '[::1]', '::1']
                : ('' !== $reqHost ? [$reqHost] : []);
            if ('' === $originHost || !in_array($originHost, $allowedHosts, true)) {
                return false;
            }
            if ('' !== $reqScheme && '' !== $originScheme && $originScheme !== $reqScheme) {
                return false;
            }
            if (null !== $reqPort && $originPort !== $reqPort) {
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
