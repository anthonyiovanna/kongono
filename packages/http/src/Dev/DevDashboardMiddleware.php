<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

use Closure;
use Kongono\Http\App;
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
     * @param (Closure(string, ?string): string)|null $oshieHandler  Optional callback ($question, $lesson) => reply
     * @param (Closure(string): string)|null          $hanteiHandler Optional callback ($diagnostic) => diagnosis
     */
    public function __construct(
        private App $app,
        private DevInspector $inspector,
        private string $prefix = '/_kongono',
        private ?Closure $oshieHandler = null,
        private ?Closure $hanteiHandler = null,
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

        $this->inspector->beginRequest();
        $start = hrtime(true);

        try {
            $response = $handler->handle($request);
            $durationMs = (hrtime(true) - $start) / 1_000_000;
            $this->inspector->recordRequest($request->getMethod(), $path, $response->getStatusCode(), $durationMs);

            return $response;
        } catch (Throwable $e) {
            $durationMs = (hrtime(true) - $start) / 1_000_000;
            $this->inspector->recordRequest($request->getMethod(), $path, 500, $durationMs, $e->getMessage());
            throw $e;
        }
    }
}
