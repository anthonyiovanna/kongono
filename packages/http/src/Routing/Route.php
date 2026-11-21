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

    /**
     * @param list<string>                               $methods
     * @param Closure|array{0: class-string, 1: string}  $handler
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $path,
        public readonly Closure|array $handler,
        private ?string $name = null,
    ) {}

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

    public function handlerLabel(): string
    {
        if (is_array($this->handler)) {
            return $this->handler[0] . '::' . $this->handler[1];
        }

        return 'Closure';
    }
}
