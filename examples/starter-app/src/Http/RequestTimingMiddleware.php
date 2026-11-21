<?php

declare(strict_types=1);

namespace StarterApp\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class RequestTimingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $started = hrtime(true);
        $response = $handler->handle($request);
        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        return $response
            ->withHeader('X-Powered-By', 'Kongono/1.0 (PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . ')')
            ->withHeader('X-Response-Time-Ms', sprintf('%.2f', $elapsedMs));
    }
}
