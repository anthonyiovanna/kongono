<?php

declare(strict_types=1);

namespace StarterApp\Http;

use Kongono\Core\Telemetry\Metrics;
use Kongono\Core\Telemetry\Tracer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class RequestTimingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?Metrics $metrics = null,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $tracer = new Tracer();
        $response = $tracer->span('app', static fn(): ResponseInterface => $handler->handle($request));
        $spans = $tracer->spans();
        $elapsedMs = $spans[0]['duration_ms'] ?? 0.0;

        $rawMethod = strtoupper($request->getMethod());
        $method = in_array($rawMethod, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], true) ? $rawMethod : 'OTHER';

        $this->metrics?->increment('kongono_http_requests_total', [
            'method' => $method,
            'status' => (string) $response->getStatusCode(),
        ]);
        $this->metrics?->observe('kongono_http_request_duration_ms', $elapsedMs, [
            'method' => $method,
        ]);

        return $response
            ->withHeader('X-Response-Time-Ms', sprintf('%.2f', $elapsedMs))
            ->withHeader('Server-Timing', $tracer->toServerTimingHeader());
    }
}
