<?php

declare(strict_types=1);

namespace Kongono\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Immutable PSR-15 middleware pipeline compiled once into nested handlers. */
final readonly class MiddlewarePipeline implements RequestHandlerInterface
{
    private RequestHandlerInterface $entry;

    /** @var list<MiddlewareInterface> */
    private array $middleware;

    public function __construct(RequestHandlerInterface $terminal, MiddlewareInterface ...$middleware)
    {
        $list = array_values($middleware);
        $this->middleware = $list;

        $next = $terminal;
        for ($i = count($list) - 1; $i >= 0; --$i) {
            $layer = $list[$i];
            $downstream = $next;
            $next = new readonly class ($layer, $downstream) implements RequestHandlerInterface {
                public function __construct(
                    private MiddlewareInterface $middleware,
                    private RequestHandlerInterface $next,
                ) {}

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }

        $this->entry = $next;
    }

    /** @return list<string> */
    public function middlewareNames(): array
    {
        return array_map(static fn(MiddlewareInterface $m): string => $m::class, $this->middleware);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->entry->handle($request);
    }
}
