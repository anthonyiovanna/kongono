<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

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

    public function __construct(
        private App $app,
        private DevInspector $inspector,
        private string $prefix = '/_kongono',
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

        if ($path === $base && 'GET' === strtoupper($request->getMethod())) {
            return Response::html($this->renderer->render($this->app, $this->inspector, $base));
        }

        if ($path === $base . '/api/traces' && 'GET' === strtoupper($request->getMethod())) {
            return Response::json($this->inspector->traces());
        }

        if ($path === $base . '/api/clear' && 'POST' === strtoupper($request->getMethod())) {
            $this->inspector->clear();

            return Response::json(['cleared' => true]);
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
