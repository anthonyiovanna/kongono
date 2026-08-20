<?php

declare(strict_types=1);

namespace Kongono\Http\Server\Tests\Fixture;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CallbackRequestHandler implements RequestHandlerInterface
{
    /** @var Closure(ServerRequestInterface): ResponseInterface */
    private readonly Closure $callback;

    /** @param Closure(ServerRequestInterface): ResponseInterface $callback */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->callback)($request);
    }
}
