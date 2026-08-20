<?php

declare(strict_types=1);

namespace Kongono\Http\Server\Tests\Fixture;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CallbackMiddleware implements MiddlewareInterface
{
    /** @var Closure(ServerRequestInterface, RequestHandlerInterface): ResponseInterface */
    private readonly Closure $callback;

    /** @param Closure(ServerRequestInterface, RequestHandlerInterface): ResponseInterface $callback */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return ($this->callback)($request, $handler);
    }
}
