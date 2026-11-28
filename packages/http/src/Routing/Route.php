<?php

declare(strict_types=1);

namespace Kongono\Http\Routing;

use Closure;
use Psr\Http\Server\MiddlewareInterface;

/** Mutable during route registration; frozen into RouteMatch at dispatch time. */
final class Route
{
    /** @var list<MiddlewareInterface> */
    private array $middleware = [];

    /** @var list<MiddlewareAttributeInterface> */
    private array $middlewareAttributes = [];

    /**
     * @param list<string>                               $methods
     * @param Closure|array{0: class-string, 1: string}  $handler
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $path,
        public readonly Closure|array $handler,
        private ?string $name = null,
        private ?string $domain = null,
    ) {}

    public function routeDomain(): ?string
    {
        return $this->domain;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function routeName(): ?string
    {
        return $this->name;
    }

    public function middleware(MiddlewareInterface ...$middleware): self
    {
        array_push($this->middleware, ...$middleware);

        return $this;
    }

    /** @return list<MiddlewareInterface> */
    public function routeMiddleware(): array
    {
        return $this->middleware;
    }

    public function middlewareAttributes(MiddlewareAttributeInterface ...$attributes): self
    {
        array_push($this->middlewareAttributes, ...$attributes);

        return $this;
    }

    /** @return list<MiddlewareAttributeInterface> */
    public function routeMiddlewareAttributes(): array
    {
        return $this->middlewareAttributes;
    }

    public function handlerLabel(): string
    {
        if (is_array($this->handler)) {
            return $this->handler[0] . '::' . $this->handler[1];
        }

        return 'Closure';
    }
}
